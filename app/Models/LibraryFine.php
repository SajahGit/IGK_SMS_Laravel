<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryFine extends Model
{
    protected $fillable = ['loan_id', 'amount', 'reason', 'paid_at', 'collected_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LibraryLoan::class, 'loan_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
