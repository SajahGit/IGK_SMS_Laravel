<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeteriaOrderItem extends Model
{
    protected $fillable = [
        'cafeteria_item_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CafeteriaOrder::class, 'order_id');
    }

    public function cafeteriaItem(): BelongsTo
    {
        return $this->belongsTo(CafeteriaItem::class);
    }
}
