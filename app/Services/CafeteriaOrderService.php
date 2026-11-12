<?php

namespace App\Services;

use App\Models\CafeteriaItem;
use App\Models\CafeteriaOrder;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CafeteriaOrderService
{
    public function placeOrder(Student $student, string $orderDate, array $lines): CafeteriaOrder
    {
        return DB::transaction(function () use ($student, $orderDate, $lines): CafeteriaOrder {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            if ($lockedStudent->status !== 'active') {
                throw ValidationException::withMessages(['student_id' => 'Only active students can place cafeteria orders.']);
            }

            $requestedItems = collect($lines)->sortBy('cafeteria_item_id');
            $items = CafeteriaItem::query()
                ->whereIn('id', $requestedItems->pluck('cafeteria_item_id'))
                ->where('is_available', true)
                ->with('inventoryItem')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $requestedItems->count()) {
                throw ValidationException::withMessages(['items' => 'One or more selected cafeteria items are unavailable.']);
            }

            $total = '0.00';
            $order = CafeteriaOrder::create([
                'student_id' => $lockedStudent->id,
                'order_date' => $orderDate,
                'status' => 'reserved',
                'total_amount' => '0.00',
            ]);

            foreach ($requestedItems as $line) {
                $item = $items->get((int) $line['cafeteria_item_id']);
                if (($item->branch_id !== null || $lockedStudent->branch_id !== null)
                    && (int) $item->branch_id !== (int) $lockedStudent->branch_id) {
                    throw ValidationException::withMessages([
                        'items' => 'The student and cafeteria items must belong to the same campus.',
                    ]);
                }

                $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($item->inventory_item_id);
                $quantity = (int) $line['quantity'];
                if (! $inventory->is_active || $inventory->quantity_on_hand < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$item->name}.",
                    ]);
                }

                $lineTotal = Money::mul($item->price, $quantity);
                $total = Money::add($total, $lineTotal);
                $order->items()->create([
                    'cafeteria_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $item->price,
                    'line_total' => $lineTotal,
                ]);
                InventoryService::recordMovement(
                    $inventory,
                    'issue',
                    $quantity,
                    'CAF-ORDER-'.$order->id,
                    'Stock reserved for cafeteria order '.$order->id,
                    auth()->id()
                );
            }

            $order->update(['total_amount' => $total]);
            AuditLogger::log('cafeteria_order_placed', $order, null, [
                'student_id' => $lockedStudent->id,
                'order_date' => $orderDate,
                'total_amount' => $total,
                'item_count' => $requestedItems->count(),
            ]);

            return $order;
        });
    }

    public function fulfill(CafeteriaOrder $order): CafeteriaOrder
    {
        return DB::transaction(function () use ($order): CafeteriaOrder {
            $locked = CafeteriaOrder::query()->lockForUpdate()->with('items.cafeteriaItem')->findOrFail($order->id);
            if ($locked->status !== 'reserved') {
                throw ValidationException::withMessages(['order' => 'Only reserved cafeteria orders can be fulfilled.']);
            }
            if ($locked->order_date->isFuture()) {
                throw ValidationException::withMessages(['order' => 'A future-dated order cannot be fulfilled yet.']);
            }

            $locked->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);
            AuditLogger::log('cafeteria_order_fulfilled', $locked, ['status' => 'reserved'], [
                'status' => 'fulfilled',
                'fulfilled_at' => $locked->fulfilled_at,
            ]);

            return $locked;
        });
    }

    public function cancel(CafeteriaOrder $order): CafeteriaOrder
    {
        return DB::transaction(function () use ($order): CafeteriaOrder {
            $locked = CafeteriaOrder::query()->lockForUpdate()->with('items.cafeteriaItem.inventoryItem')->findOrFail($order->id);
            if ($locked->status !== 'reserved') {
                throw ValidationException::withMessages(['order' => 'Only reserved cafeteria orders can be cancelled.']);
            }

            foreach ($locked->items->sortBy(fn ($line) => $line->cafeteriaItem->inventory_item_id) as $line) {
                $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($line->cafeteriaItem->inventory_item_id);
                InventoryService::recordMovement(
                    $inventory,
                    'receipt',
                    $line->quantity,
                    'CAF-ORDER-'.$locked->id,
                    'Stock returned for cancelled cafeteria order '.$locked->id,
                    auth()->id()
                );
            }

            $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            AuditLogger::log('cafeteria_order_cancelled', $locked, ['status' => 'reserved'], [
                'status' => 'cancelled',
                'cancelled_at' => $locked->cancelled_at,
            ]);

            return $locked;
        });
    }
}
