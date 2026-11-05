<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Offsetting record for a reversed payment (SOP PR-5, TC-FIN-18). The original payment row
 * is retained; this row records who reversed it, when, why, and the amount that was offset.
 */
class PaymentReversal extends Model
{
    protected $fillable = ['payment_id', 'invoice_id', 'amount', 'reason', 'reversed_by', 'reversed_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
