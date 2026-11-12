<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayHereTransaction extends Model
{
    protected $table = 'payhere_transactions';

    protected $fillable = [
        'invoice_id',
        'order_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'notification',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'notification' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
