<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    /** @param  array<int, array{sku:string,name:string,quantity_on_hand:int,reorder_level:int}>  $items */
    public function __construct(public array $items) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $names = collect($this->items)->pluck('name')->take(5)->implode(', ');
        $more = count($this->items) > 5 ? ' and '.(count($this->items) - 5).' more' : '';

        return [
            'title' => 'Low stock alert',
            'body' => count($this->items).' inventory item(s) are at or below their reorder level: '.$names.$more.'.',
            'item_count' => count($this->items),
            'url' => url('/'),
        ];
    }
}
