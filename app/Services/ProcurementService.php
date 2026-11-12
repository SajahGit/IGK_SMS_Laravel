<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Scopes\BranchOwned;
use App\Models\Supplier;
use App\Support\AuditLogger;
use App\Support\BranchContext;
use App\Support\NumberSequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public static function create(array $data, int $actorId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actorId) {
            [, $branchId] = self::validateOrderRelations($data['supplier_id'], $data['items']);
            $order = PurchaseOrder::create([
                'order_no' => self::nextOrderNumber(),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $branchId,
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'created_by' => $actorId,
                'notes' => $data['notes'] ?? null,
            ]);

            self::syncItems($order, $data['items']);
            AuditLogger::log('created_purchase_order', $order, null, $order->toArray());

            return $order;
        });
    }

    public static function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            self::ensureDraft($locked);
            self::validateOrderRelations($data['supplier_id'], $data['items'], $locked->branch_id);
            $before = $locked->toArray();
            $locked->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            self::syncItems($locked, $data['items']);
            AuditLogger::log('updated_purchase_order', $locked, $before, $locked->fresh()->toArray());

            return $locked->fresh();
        });
    }

    public static function submit(PurchaseOrder $order): void
    {
        self::transition($order, ['draft'], ['status' => 'submitted'], 'submitted_purchase_order');
    }

    public static function approve(PurchaseOrder $order, int $actorId): void
    {
        self::transition($order, ['submitted'], [
            'status' => 'approved',
            'approved_by' => $actorId,
            'approved_at' => now(),
        ], 'approved_purchase_order');
    }

    public static function receive(PurchaseOrder $order, array $quantities, int $actorId, ?string $reference, ?string $notes): void
    {
        DB::transaction(function () use ($order, $quantities, $actorId, $reference, $notes) {
            $locked = PurchaseOrder::query()->lockForUpdate()->with('items')->findOrFail($order->id);
            if (! in_array($locked->status, ['approved', 'partially_received'], true)) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'Only approved orders with remaining quantities can be received.',
                ]);
            }

            $unknownLineIds = array_diff(array_map('intval', array_keys($quantities)), $locked->items->modelKeys());
            if ($unknownLineIds !== []) {
                throw ValidationException::withMessages([
                    'items' => 'The receipt contains a line that is not part of this purchase order.',
                ]);
            }

            $receivedAnything = false;
            foreach ($locked->items as $line) {
                $quantity = (int) ($quantities[$line->id] ?? 0);
                $remaining = $line->quantity_ordered - $line->quantity_received;

                if ($quantity < 0 || $quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'items.'.$line->id => 'Received quantity cannot exceed the remaining quantity on the order.',
                    ]);
                }

                if ($quantity === 0) {
                    continue;
                }

                $receivedAnything = true;
                $inventoryItem = InventoryItem::withoutGlobalScope(BranchOwned::class)
                    ->lockForUpdate()
                    ->findOrFail($line->inventory_item_id);
                if (! self::sameBranch($inventoryItem->branch_id, $locked->branch_id)) {
                    throw ValidationException::withMessages([
                        'items.'.$line->id => 'Purchase-order stock must belong to the same campus as the order.',
                    ]);
                }

                InventoryService::recordMovement(
                    $inventoryItem,
                    'receipt',
                    $quantity,
                    $reference ?: $locked->order_no,
                    trim(($notes ? $notes.' ' : '').'Purchase order '.$locked->order_no),
                    $actorId
                );
                $line->update(['quantity_received' => $line->quantity_received + $quantity]);
            }

            if (! $receivedAnything) {
                throw ValidationException::withMessages([
                    'items' => 'Enter at least one quantity to receive.',
                ]);
            }

            $complete = $locked->items->every(
                fn (PurchaseOrderItem $line) => $line->quantity_received >= $line->quantity_ordered
            );
            $before = $locked->toArray();
            $locked->update([
                'status' => $complete ? 'received' : 'partially_received',
                'received_by' => $actorId,
                'received_at' => $complete ? now() : $locked->received_at,
            ]);
            AuditLogger::log('received_purchase_order', $locked, $before, [
                'status' => $locked->status,
                'received_quantities' => $quantities,
            ]);
        });
    }

    public static function cancel(PurchaseOrder $order, int $actorId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A cancellation reason is required.']);
        }

        DB::transaction(function () use ($order, $actorId, $reason) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($locked->status, ['draft', 'submitted', 'approved'], true)) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'An order can only be cancelled before any goods are received.',
                ]);
            }
            $before = $locked->toArray();
            $locked->update(['status' => 'cancelled', 'notes' => trim(($locked->notes ? $locked->notes."\n" : '').'Cancellation: '.$reason)]);
            AuditLogger::log('cancelled_purchase_order', $locked, $before, [
                'status' => 'cancelled',
                'reason' => $reason,
                'cancelled_by' => $actorId,
            ]);
        });
    }

    public static function deleteDraft(PurchaseOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            self::ensureDraft($locked);
            $before = $locked->toArray();
            AuditLogger::log('deleted_purchase_order', $locked, $before, null);
            $locked->delete();
        });
    }

    private static function nextOrderNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";
        $sequence = NumberSequence::next(
            "purchase-order:{$year}",
            fn () => NumberSequence::highestSuffix(PurchaseOrder::class, 'order_no', $prefix)
        );

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private static function syncItems(PurchaseOrder $order, array $items): void
    {
        foreach ($order->items as $line) {
            $line->delete();
        }

        foreach ($items as $item) {
            $order->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $item['unit_cost'],
            ]);
        }
    }

    /**
     * @return array{0: Supplier, 1: ?int}
     */
    private static function validateOrderRelations(int $supplierId, array $items, ?int $orderBranchId = null): array
    {
        $supplier = Supplier::withoutGlobalScope(BranchOwned::class)->find($supplierId);
        if (! $supplier || ! $supplier->is_active) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Choose an active supplier.',
            ]);
        }

        $user = Auth::user();
        $contextBranchId = $orderBranchId ?? BranchContext::activeBranchId($user);
        if ($user && ! BranchContext::isGlobalAdmin($user) && $contextBranchId === null && BranchContext::hasActiveBranches()) {
            throw ValidationException::withMessages([
                'branch_id' => 'Assign this account to a campus before creating purchase orders.',
            ]);
        }

        $supplierBranchId = $supplier->branch_id === null ? null : (int) $supplier->branch_id;
        if ($contextBranchId !== null && $supplierBranchId !== $contextBranchId) {
            throw ValidationException::withMessages([
                'supplier_id' => 'The supplier must belong to the active campus.',
            ]);
        }

        $itemIds = collect($items)->pluck('inventory_item_id')->map(fn ($id) => (int) $id);
        if ($itemIds->count() !== $itemIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Each inventory item can appear only once in a purchase order.',
            ]);
        }

        $inventoryItems = InventoryItem::withoutGlobalScope(BranchOwned::class)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($inventoryItems->count() !== $itemIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected inventory items are unavailable.',
            ]);
        }

        foreach ($inventoryItems as $inventoryItem) {
            if (! $inventoryItem->is_active || ! self::sameBranch($inventoryItem->branch_id, $supplierBranchId)) {
                throw ValidationException::withMessages([
                    'items' => 'Suppliers, purchase orders, and inventory items must belong to the same campus.',
                ]);
            }
        }

        return [$supplier, $supplierBranchId];
    }

    private static function sameBranch(?int $left, ?int $right): bool
    {
        return $left === $right;
    }

    private static function transition(PurchaseOrder $order, array $allowed, array $changes, string $action): void
    {
        DB::transaction(function () use ($order, $allowed, $changes, $action) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($locked->status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'This purchase order cannot transition from its current status.',
                ]);
            }
            $before = $locked->toArray();
            $locked->update($changes);
            AuditLogger::log($action, $locked, $before, $locked->toArray());
        });
    }

    private static function ensureDraft(PurchaseOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'purchase_order' => 'Only draft purchase orders can be edited or deleted.',
            ]);
        }
    }
}
