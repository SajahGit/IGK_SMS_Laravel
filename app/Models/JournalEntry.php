<?php

namespace App\Models;

use App\Models\Scopes\BranchOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new BranchOwned);
    }

    protected $fillable = ['entry_no', 'entry_date', 'currency', 'description', 'reference', 'branch_id', 'source_type', 'source_id', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
