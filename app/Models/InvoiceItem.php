<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One billed line of an invoice (SOP §11 invoice_items). A negative amount is a discount line.
 * Lines are written once when the invoice is issued and never edited afterwards.
 */
class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'fee_structure_id', 'fee_item_id', 'description', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
