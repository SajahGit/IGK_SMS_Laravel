<?php

namespace App\Services;

use App\Events\InvoiceIssued;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Support\AuditLogger;
use App\Support\Money;
use App\Support\NumberSequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceService
{
    /** Students with these statuses are never billed by a bulk run. */
    public const NON_BILLABLE_STUDENT_STATUSES = ['applicant', 'withdrawn', 'graduated'];

    /**
     * Next invoice number INV-{YYYY}-{0001}. Drawn from a locked counter row (see
     * NumberSequence) so concurrent bulk runs cannot collide and a rolled-back batch
     * never burns numbers. Call inside the invoice's own transaction.
     */
    public static function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        $sequence = NumberSequence::next(
            "invoice:{$year}",
            fn () => NumberSequence::highestSuffix(Invoice::class, 'invoice_no', $prefix)
        );

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Issue one invoice for one student and term (US-F1, SOP M20).
     *
     * Rules:
     *  - subtotal = sum of the ACTIVE fee structures passed in (exact decimal maths via Money);
     *    none active => ValidationException, nothing written.
     *  - one invoice_items line per fee structure; an optional discount is stored as a
     *    separate NEGATIVE line (SOP Q10) and in invoices.discount. total = subtotal - discount.
     *  - discount may not exceed the subtotal.
     *  - status "issued" (or "draft" when $asDraft), amount_paid 0.00, balance = total.
     *    A draft takes no payments and is hidden from students until it is issued.
     *  - Idempotent: a student that already has a non-cancelled invoice for the term
     *    (or for "no term" when $termId is null) is NOT invoiced again; null is returned.
     *  - MID-TERM JOINERS ARE CHARGED IN FULL. Pro-rating is explicitly out of scope
     *    (SOP US-F1), so a student who joined after the term started pays the full fee.
     *
     * Safe to call on its own or inside the bulk transaction (nested transactions
     * become savepoints, so a failure in the bulk run rolls the whole batch back).
     *
     * @param  Collection<int, FeeStructure>  $feeStructures
     * @return Invoice|null the new invoice, or null when the student was already invoiced
     */
    public static function generateForStudent(
        Student $student,
        ?int $termId,
        string $issueDate,
        string $dueDate,
        Collection $feeStructures,
        string|float|int|null $discount = '0.00',
        array $optionalItemIds = [],
        bool $asDraft = false
    ): ?Invoice {
        $fees = $feeStructures->filter(fn (FeeStructure $fee) => (bool) $fee->is_active);

        if ($fees->isEmpty()) {
            throw ValidationException::withMessages([
                'fee_structure_ids' => 'Please select at least one active fee structure.',
            ]);
        }

        $currencies = $fees->pluck('currency')->unique()->values();
        if ($currencies->count() !== 1) {
            throw ValidationException::withMessages([
                'fee_structure_ids' => 'All fee structures on one invoice must use the same currency.',
            ]);
        }
        $currency = $currencies->first();

        // One invoice line per fee item (mandatory ones, plus any optional ones that were ticked);
        // a structure without items bills its own amount as a single line.
        $lines = collect();
        foreach ($fees as $fee) {
            foreach ($fee->billableLines($optionalItemIds) as $line) {
                $lines->push($line + ['fee_structure_id' => $fee->id]);
            }
        }

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'fee_structure_ids' => 'The selected fee structures have no billable items.',
            ]);
        }

        $subtotal = '0.00';
        foreach ($lines as $line) {
            $subtotal = Money::add($subtotal, $line['amount']);
        }

        $discount = Money::add($discount, '0.00');

        if (Money::comp($discount, '0.00') < 0) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot be negative.']);
        }

        if (Money::comp($discount, $subtotal) === 1) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot be greater than the amount billed ('.Money::format($subtotal).').']);
        }

        $total = Money::sub($subtotal, $discount);

        $status = $asDraft ? 'draft' : 'issued';

        return DB::transaction(function () use ($student, $termId, $issueDate, $dueDate, $lines, $subtotal, $discount, $total, $currency, $status, $asDraft) {
            // Lock the student row so two generation runs cannot both pass the guard below.
            Student::whereKey($student->id)->lockForUpdate()->first();

            $existing = Invoice::where('student_id', $student->id)
                ->where('status', '!=', 'cancelled')
                ->when(
                    $termId,
                    fn ($query) => $query->where('term_id', $termId),
                    fn ($query) => $query->whereNull('term_id')
                )
                ->exists();

            if ($existing) {
                return null;
            }

            $invoice = Invoice::create([
                'student_id' => $student->id,
                'branch_id' => $student->branch_id,
                'term_id' => $termId,
                'invoice_no' => self::nextInvoiceNumber(),
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => $currency,
                'amount_paid' => '0.00',
                'balance' => $total,
                'status' => $status,
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'fee_structure_id' => $line['fee_structure_id'],
                    'fee_item_id' => $line['fee_item_id'],
                    'description' => $line['description'],
                    'amount' => $line['amount'],
                ]);
            }

            if (Money::comp($discount, '0.00') > 0) {
                $invoice->items()->create([
                    'fee_structure_id' => null,
                    'description' => 'Discount',
                    'amount' => Money::sub('0.00', $discount),
                ]);
            }

            AuditLogger::log('created', $invoice, null, $invoice->toArray());

            // A draft is not announced to the student until it is issued.
            // A notification failure never rolls back the invoice.
            if (! $asDraft) {
                try {
                    InvoiceIssued::dispatch($invoice);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            return $invoice;
        });
    }

    /**
     * Bulk-invoice a whole class for a term. All-or-nothing: if any student fails,
     * the whole batch is rolled back (no half batch). Re-running is idempotent and
     * reports how many students were skipped because they were already invoiced.
     *
     * Only fee structures of this class that are active AND either apply to every term
     * (term_id null) or to the chosen term are used. Applicant, withdrawn and graduated
     * students are not billed.
     *
     * @param  array<int, int|string>  $feeStructureIds
     * @return array{created: int, skipped: int, total: int}
     */
    public static function bulkGenerateForClass(
        int $classId,
        ?int $termId,
        string $issueDate,
        string $dueDate,
        array $feeStructureIds,
        string|float|int|null $discount = '0.00',
        array $optionalItemIds = [],
        bool $asDraft = false
    ): array {
        return DB::transaction(function () use ($classId, $termId, $issueDate, $dueDate, $feeStructureIds, $discount, $optionalItemIds, $asDraft) {
            $class = SchoolClass::findOrFail($classId);
            $academicYearId = $termId
                ? Term::whereKey($termId)->value('academic_year_id')
                : $class->academic_year_id;

            // SOP §15.1 / US-F1: a fee structure must exist for this program+year
            // (program-wide structures), or — legacy, still supported — for this
            // class+term directly.
            $fees = FeeStructure::with('items')
                ->billableFor($class->program_id, $academicYearId, $classId, $termId)
                ->whereIn('id', $feeStructureIds)
                ->get();

            if ($fees->isEmpty()) {
                throw ValidationException::withMessages([
                    'fee_structure_ids' => 'Please select at least one active fee structure that applies to this class and term.',
                ]);
            }

            $students = Student::where('class_id', $classId)
                ->whereNotIn('status', self::NON_BILLABLE_STUDENT_STATUSES)
                ->orderBy('id')
                ->get();
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($students as $student) {
                $invoice = self::generateForStudent($student, $termId, $issueDate, $dueDate, $fees, $discount, $optionalItemIds, $asDraft);

                $invoice === null ? $skippedCount++ : $createdCount++;
            }

            return [
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'total' => $students->count(),
            ];
        });
    }

    /**
     * Issue a draft invoice (SOP M20 "issue action"): draft -> issued. Only now can it take
     * payments and does the student see it and get notified.
     */
    public static function issue(Invoice $invoice): Invoice
    {
        $issued = DB::transaction(function () use ($invoice) {
            /** @var Invoice $locked */
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['invoice' => 'Only a draft invoice can be issued.']);
            }

            $locked->update(['status' => 'issued']);

            AuditLogger::log('invoice_issued', $locked, ['status' => 'draft'], ['status' => 'issued']);

            return $locked;
        });

        try {
            InvoiceIssued::dispatch($issued);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $issued;
    }

    /**
     * Issue every draft invoice of a class (optionally limited to one term).
     *
     * @return int number of invoices issued
     */
    public static function issueDraftsForClass(int $classId, ?int $termId = null): int
    {
        $drafts = Invoice::where('status', 'draft')
            ->whereHas('student', fn ($q) => $q->where('class_id', $classId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->orderBy('id')
            ->get();

        foreach ($drafts as $draft) {
            self::issue($draft);
        }

        return $drafts->count();
    }

    /**
     * Cancel an invoice (SOP: invoices are never hard-deleted). Only an invoice with no
     * money held against it can be cancelled - reverse its payments first. A reason is mandatory.
     */
    public static function cancel(Invoice $invoice, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            /** @var Invoice $locked */
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($locked->isCancelled()) {
                throw ValidationException::withMessages(['cancellation_reason' => 'This invoice is already cancelled.']);
            }

            $hasLivePayments = Payment::where('invoice_id', $locked->id)->whereNull('reversed_at')->exists();

            if ($hasLivePayments || Money::comp($locked->amount_paid, '0.00') > 0) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'This invoice has payments. Reverse them first, then cancel the invoice.',
                ]);
            }

            $old = $locked->only(['status', 'balance']);

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            AuditLogger::log('invoice_cancelled', $locked, $old, [
                'status' => 'cancelled',
                'reason' => $reason,
            ]);

            return $locked;
        });
    }
}
