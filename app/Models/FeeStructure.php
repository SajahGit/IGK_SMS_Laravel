<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'term_id',
        'program_id',
        'academic_year_id',
        'name',
        'amount',
        'currency',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * SOP §15.1 / US-F1: the billing basis is program + academic year, not class + term.
     * A structure created the old way (class_id/term_id only, e.g. existing data, existing
     * tests) still works: program_id/academic_year_id are derived here so every structure
     * — old or new — has the SOP-exact scope set. A structure created the new way
     * (program_id/academic_year_id given directly, no class_id) is program-wide.
     */
    protected static function booted(): void
    {
        static::saving(function (FeeStructure $fee) {
            $fee->currency = strtoupper($fee->currency ?: config('school.default_currency', 'LKR'));

            if (! $fee->program_id && $fee->class_id) {
                $fee->program_id = SchoolClass::whereKey($fee->class_id)->value('program_id');
            }

            if (! $fee->academic_year_id) {
                if ($fee->term_id) {
                    $fee->academic_year_id = Term::whereKey($fee->term_id)->value('academic_year_id');
                } elseif ($fee->class_id) {
                    $fee->academic_year_id = SchoolClass::whereKey($fee->class_id)->value('academic_year_id');
                }
            }
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** True when this structure is defined program-wide (SOP-exact), not tied to one class. */
    public function isProgramWide(): bool
    {
        return $this->class_id === null;
    }

    /**
     * SOP §15.1 / US-F1: active structures billable for a student of this program in this
     * academic year — either a program-wide structure for that program+year, or (legacy,
     * still supported) a structure tied to the given class for the given term.
     */
    public function scopeBillableFor($query, ?int $programId, ?int $academicYearId, ?int $classId, ?int $termId)
    {
        return $query->where('is_active', true)->where(function ($q) use ($programId, $academicYearId, $classId, $termId) {
            // Program-wide branch: only a structure with no class_id (truly program-wide)
            // qualifies here. class_id/academic_year_id are auto-derived on EVERY
            // structure (see booted()), so without the whereNull('class_id') guard this
            // would also match another class's legacy structure that happens to share
            // the same program + academic year.
            $q->when($programId, fn ($qq) => $qq->orWhere(function ($qqq) use ($programId, $academicYearId) {
                $qqq->whereNull('class_id')
                    ->where('program_id', $programId)
                    ->when($academicYearId, fn ($qqqq) => $qqqq->where('academic_year_id', $academicYearId));
            }));

            $q->when($classId, fn ($qq) => $qq->orWhere(function ($qqq) use ($classId, $termId) {
                $qqq->where('class_id', $classId)
                    ->where(function ($w) use ($termId) {
                        $w->whereNull('term_id');
                        if ($termId) {
                            $w->orWhere('term_id', $termId);
                        }
                    });
            }));
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeItem::class)->orderBy('id');
    }

    /**
     * The invoice lines this structure produces.
     *
     * - With fee items: every mandatory item, plus the optional items whose ids are passed in.
     * - Without fee items: one line for the structure's own amount (the original behaviour).
     *
     * @param  array<int, int|string>  $optionalItemIds
     * @return Collection<int, array{description: string, amount: string, fee_item_id: int|null}>
     */
    public function billableLines(array $optionalItemIds = []): Collection
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($items->isEmpty()) {
            return collect([[
                'description' => $this->name,
                'amount' => Money::add($this->amount, '0.00'),
                'fee_item_id' => null,
            ]]);
        }

        $optional = array_map('intval', $optionalItemIds);

        return $items
            ->filter(fn (FeeItem $item) => $item->is_mandatory || in_array((int) $item->id, $optional, true))
            ->map(fn (FeeItem $item) => [
                'description' => $this->name.' - '.$item->name,
                'amount' => Money::add($item->amount, '0.00'),
                'fee_item_id' => (int) $item->id,
            ])
            ->values();
    }

    /**
     * When a structure has items, its amount is the exact sum of its mandatory items.
     */
    public function syncAmountFromItems(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $total = '0.00';
        foreach ($items->where('is_mandatory', true) as $item) {
            $total = Money::add($total, $item->amount);
        }

        $this->update(['amount' => $total]);
    }

    /**
     * SOP §11: "Cannot edit once invoices reference it - version by creating a new structure."
     *
     * Referenced = a non-cancelled invoice line was built from it, or a non-cancelled invoice
     * already exists for a student of this class for the same term.
     */
    public function isReferencedByInvoices(): bool
    {
        if ($this->class_id) {
            $byClassAndTerm = Invoice::where('status', '!=', 'cancelled')
                ->whereHas('student', fn ($q) => $q->where('class_id', $this->class_id))
                ->when(
                    $this->term_id,
                    fn ($q) => $q->where('term_id', $this->term_id),
                    fn ($q) => $q->whereNull('term_id')
                )
                ->exists();

            if ($byClassAndTerm) {
                return true;
            }
        }

        // SOP-exact (program-wide) scope: any non-cancelled invoice for a student of
        // this program, in this academic year, is a reference. Only applies to a
        // program-wide structure (class_id null) — a legacy class-based structure
        // also carries an auto-derived program_id, but that must not widen its own
        // immutability check to every other class in the same program.
        if ($this->isProgramWide() && $this->program_id) {
            $byProgramAndYear = Invoice::where('status', '!=', 'cancelled')
                ->whereHas('student', fn ($q) => $q->where('program_id', $this->program_id)
                    ->when($this->academic_year_id, fn ($qq) => $qq->where('academic_year_id', $this->academic_year_id)))
                ->exists();

            if ($byProgramAndYear) {
                return true;
            }
        }

        return InvoiceItem::where('fee_structure_id', $this->id)
            ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->exists();
    }
}
