<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public static function recordMovement(
        InventoryItem $item,
        string $type,
        int $quantity,
        ?string $reference = null,
        ?string $notes = null,
        ?int $recordedBy = null
    ): InventoryMovement {
        $minimumQuantity = $type === 'adjustment' ? 0 : 1;
        if (! in_array($type, ['receipt', 'issue', 'adjustment'], true) || $quantity < $minimumQuantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Choose a valid stock movement and a positive quantity.',
            ]);
        }

        return DB::transaction(function () use ($item, $type, $quantity, $reference, $notes, $recordedBy) {
            $lockedItem = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $change = match ($type) {
                'receipt' => $quantity,
                'issue' => -$quantity,
                'adjustment' => $quantity - $lockedItem->quantity_on_hand,
            };
            $balance = $lockedItem->quantity_on_hand + $change;

            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'The issue exceeds the available stock.',
                ]);
            }

            if ($change === 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'The adjusted quantity already matches the recorded balance.',
                ]);
            }

            $lockedItem->update(['quantity_on_hand' => $balance]);
            $movement = $lockedItem->movements()->create([
                'recorded_by' => $recordedBy ?? auth()->id(),
                'type' => $type,
                'quantity_change' => $change,
                'balance_after' => $balance,
                'reference' => $reference,
                'notes' => $notes,
            ]);
            AuditLogger::log('inventory_stock_moved', $movement, null, [
                'item_id' => $lockedItem->id,
                'type' => $type,
                'quantity_change' => $change,
                'balance_after' => $balance,
            ]);

            return $movement;
        });
    }
}
