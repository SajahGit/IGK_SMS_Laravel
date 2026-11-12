<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'generated_by',
        'approved_by',
        'paid_by',
        'payout_account_id',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payoutAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payout_account_id');
    }
}
