<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a fee structure (SOP §11 fee_items). Optional items (is_mandatory = false)
 * are only billed when they are ticked on the invoice generation form.
 */
class FeeItem extends Model
{
    protected $fillable = ['fee_structure_id', 'name', 'amount', 'is_mandatory'];

    protected $attributes = [
        'is_mandatory' => true,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_mandatory' => 'boolean',
        ];
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
