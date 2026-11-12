<?php

namespace App\Models;

use App\Models\Scopes\BranchOwned;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'branch_id',
        'term_id',
        'invoice_no',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'total',
        'amount_paid',
        'balance',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $attributes = [
        'subtotal' => '0.00',
        'discount' => '0.00',
        'amount_paid' => '0.00',
        'status' => 'issued',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchOwned);
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Invoices that count as real money: not cancelled and not a draft.
     * Use this for every total, report and ledger.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'draft']);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid' => '<span class="badge bg-success">Paid</span>',
            'partially_paid' => '<span class="badge bg-info text-dark">Partially Paid</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            default => '<span class="badge bg-warning text-dark">Issued</span>',
        };
    }
}
