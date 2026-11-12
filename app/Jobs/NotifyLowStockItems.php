<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/** Background job: one daily low-stock alert to administrators. */
class NotifyLowStockItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('name')
            ->get(['sku', 'name', 'quantity_on_hand', 'reorder_level'])
            ->map(fn ($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_on_hand' => (int) $item->quantity_on_hand,
                'reorder_level' => (int) $item->reorder_level,
            ])
            ->all();

        if ($items === [] || ! Cache::add('low-stock-alert:'.today()->toDateString(), true, now()->addDay())) {
            return;
        }

        $admins = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereIn('role', ['admin', 'super-admin'])
                    ->orWhereHas('roles', fn ($roles) => $roles->whereIn('name', ['Institution Admin', 'Super Admin']));
            })
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new LowStockNotification($items));
        }
    }
}
