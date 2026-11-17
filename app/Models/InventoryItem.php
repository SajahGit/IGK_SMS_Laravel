<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'branch_id',
        'name',
        'category',
        'unit',
        'quantity_on_hand',
        'reorder_level',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }
}
