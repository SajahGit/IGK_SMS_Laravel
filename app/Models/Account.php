<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    public const TYPES = ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];

    protected $fillable = ['code', 'name', 'type', 'parent_id', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }
}
