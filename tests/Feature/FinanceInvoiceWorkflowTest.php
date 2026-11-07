<?php

namespace Tests\Feature;

use App\Jobs\GenerateTermInvoices;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

/**
 * M20 invoice generation - SOP US-F1, TC-FIN-01..05 (Rajiya, Day 1).
 */
class FinanceInvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;

    private int $studentCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
    }

    private function makeStudent(?int $classId = null, array $overrides = []): Student
    {
        $this->studentCounter++;
        $user = User::create([
            'name' => 'Student '.$this->studentCounter,
            'email' => "fin-student{$this->studentCounter}@school.test",
            'password' => 'password',
            'role' => 'student',
        ]);

        return Student::create($overrides + [
            'user_id' => $user->id,
            'class_id' => $classId ?? $this->class->id,
            'admission_no' => sprintf('FIN%03d', $this->studentCounter),
        ]);
    }

    private function makeFee(string $name, string $amount, bool $active = true): FeeStructure
    {
        return FeeStructure::create([
            'class_id' => $this->class->id,
            'name' => $name,
            'amount' => $amount,
            'is_active' => $active,
        ]);
    }

    private function bulk(array $feeIds, ?int $termId = null): array
    {
        return InvoiceService::bulkGenerateForClass(
            $this->class->id,
            $termId,
            '2026-11-03',
            '2026-12-03',
            $feeIds
        );
    }

    /** TC-FIN-01: one invoice per student; total = sum of fee items; balance = total. */
    public function test_tc_fin_01_one_invoice_per_student_total_is_sum_of_fee_items(): void
    {
        $students = collect([$this->makeStudent(), $this->makeStudent(), $this->makeStudent()]);
        $tuition = $this->makeFee('Tuition', '10000.00');
        $lab = $this->makeFee('Lab', '2500.50');

        $result = $this->bulk([$tuition->id, $lab->id]);

        $this->assertSame(['created' => 3, 'skipped' => 0, 'total' => 3], $result);
        $this->assertSame(3, Invoice::count());

        foreach ($students as $student) {
            $invoices = Invoice::where('student_id', $student->id)->get();
            $this->assertCount(1, $invoices);

            $invoice = $invoices->first();
            $this->assertSame('12500.50', $invoice->total);
            $this->assertSame('0.00', $invoice->amount_paid);
            $this->assertSame('12500.50', $invoice->balance);
            $this->assertSame('issued', $invoice->status);
            $this->assertSame('2026-11-03', $invoice->issue_date->toDateString());
            $this->assertSame('2026-12-03', $invoice->due_date->toDateString());
        }
    }

    /** TC-FIN-02: re-running creates zero new invoices and reports the skipped count. */
    public function test_tc_fin_02_rerun_creates_zero_and_reports_skipped(): void
    {
        $this->makeStudent();
        $this->makeStudent();
        $fee = $this->makeFee('Tuition', '8000.00');

        $this->bulk([$fee->id]);
        $second = $this->bulk([$fee->id]);

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['skipped']);
        $this->assertSame(2, Invoice::count());
    }

    /** TC-FIN-02 via the queued job: the stored message shows the skipped count. */
    public function test_tc_fin_02_job_stores_created_then_skipped_summary(): void
    {
        $this->makeStudent();
        $this->makeStudent();
        $fee = $this->makeFee('Tuition', '8000.00');

        GenerateTermInvoices::dispatchSync($this->class->id, null, '2026-11-03', '2026-12-03', [$fee->id], 'run-1');
        $first = GenerateTermInvoices::result('run-1');
        $this->assertSame('completed', $first['status']);
        $this->assertSame('2 created, 0 skipped (already invoiced).', $first['message']);

        GenerateTermInvoices::dispatchSync($this->class->id, null, '2026-11-03', '2026-12-03', [$fee->id], 'run-2');
        $second = GenerateTermInvoices::result('run-2');
        $this->assertSame('0 created, 2 skipped (already invoiced).', $second['message']);
        $this->assertSame(2, Invoice::count());
    }

    /** TC-FIN-03: invoice numbers are sequential and unique. */
    public function test_tc_fin_03_invoice_numbers_are_sequential_and_unique(): void
    {
        $this->makeStudent();
        $this->makeStudent();
        $this->makeStudent();
        $fee = $this->makeFee('Tuition', '5000.00');

        $this->bulk([$fee->id]);

        $year = date('Y');
        $numbers = Invoice::orderBy('id')->pluck('invoice_no')->all();
        $this->assertSame(["INV-{$year}-0001", "INV-{$year}-0002", "INV-{$year}-0003"], $numbers);
        $this->assertCount(3, array_unique($numbers));

        // A student added later continues the sequence - no gap, no reuse.
        $this->makeStudent();
        $this->bulk([$fee->id]);

        $this->assertSame("INV-{$year}-0004", Invoice::orderByDesc('id')->value('invoice_no'));
        $this->assertSame(4, Invoice::distinct()->count('invoice_no'));
    }

    /** TC-FIN-04: zero active fee items is rejected and nothing is written. */
    public function test_tc_fin_04_zero_active_fee_items_is_rejected(): void
    {
        $this->makeStudent();
        $inactive = $this->makeFee('Old fee', '3000.00', false);

        try {
            $this->bulk([$inactive->id]);
            $this->fail('Expected ValidationException when no active fee structure is selected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fee_structure_ids', $e->errors());
        }

        try {
            $this->bulk([]);
            $this->fail('Expected ValidationException when no fee structure is selected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fee_structure_ids', $e->errors());
        }

        $this->assertSame(0, Invoice::count());
    }

    /** TC-FIN-05: a failure part-way through leaves no half batch. */
    public function test_tc_fin_05_partial_failure_leaves_nothing(): void
    {
        $this->makeStudent();
        $this->makeStudent();
        $this->makeStudent();
        $fee = $this->makeFee('Tuition', '6000.00');

        $created = 0;
        Invoice::creating(function () use (&$created) {
            $created++;
            if ($created === 2) {
                throw new RuntimeException('Forced failure on the second invoice.');
            }
        });

        try {
            $this->bulk([$fee->id]);
            $this->fail('Expected the forced failure to abort the batch.');
        } catch (RuntimeException $e) {
            $this->assertSame('Forced failure on the second invoice.', $e->getMessage());
        } finally {
            Invoice::flushEventListeners();
        }

        $this->assertSame(0, Invoice::count(), 'The first invoice must be rolled back too.');

        // After the fault is gone, the same run succeeds in full.
        $result = $this->bulk([$fee->id]);
        $this->assertSame(3, $result['created']);
    }

    /** US-F1 edge case: a mid-term joiner is charged the full fee (no pro-rating). */
    public function test_mid_term_joiner_is_charged_in_full(): void
    {
        $early = $this->makeStudent(null, ['admission_date' => '2026-09-01']);
        $late = $this->makeStudent(null, ['admission_date' => '2026-11-20']);
        $fee = $this->makeFee('Tuition', '9000.00');

        $this->bulk([$fee->id]);

        $this->assertSame('9000.00', Invoice::where('student_id', $early->id)->value('total'));
        $this->assertSame('9000.00', Invoice::where('student_id', $late->id)->value('total'));
    }

    /** generateForStudent(): returns the invoice once, then null on the second call. */
    public function test_generate_for_student_is_idempotent(): void
    {
        $student = $this->makeStudent();
        $this->makeFee('Tuition', '4000.00');
        $fees = FeeStructure::where('class_id', $this->class->id)->get();

        $first = InvoiceService::generateForStudent($student, null, '2026-11-03', '2026-12-03', $fees);
        $second = InvoiceService::generateForStudent($student, null, '2026-11-03', '2026-12-03', $fees);

        $this->assertInstanceOf(Invoice::class, $first);
        $this->assertSame('4000.00', $first->balance);
        $this->assertNull($second);
        $this->assertSame(1, Invoice::count());
    }

    /** A cancelled invoice does not block a fresh invoice for the same student and term. */
    public function test_cancelled_invoice_does_not_block_regeneration(): void
    {
        $student = $this->makeStudent();
        $fee = $this->makeFee('Tuition', '4000.00');

        $this->bulk([$fee->id]);
        Invoice::where('student_id', $student->id)->update(['status' => 'cancelled']);

        $result = $this->bulk([$fee->id]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(2, Invoice::where('student_id', $student->id)->count());
    }
}
