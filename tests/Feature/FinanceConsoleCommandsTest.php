<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP §11.3 / §12: school:verify-integrity, school:generate-invoices, school:close-term.
 */
class FinanceConsoleCommandsTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    public function test_verify_integrity_passes_on_clean_data(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()), '9000.00');
        PaymentService::recordPayment($invoice->id, '4000.00', 'cash', null, today()->toDateString());

        $this->artisan('school:verify-integrity')->assertExitCode(0);
    }

    public function test_verify_integrity_fails_when_a_balance_is_altered(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()), '9000.00');
        PaymentService::recordPayment($invoice->id, '4000.00', 'cash', null, today()->toDateString());

        DB::table('invoices')->where('id', $invoice->id)->update(['balance' => '1.00']);

        $this->artisan('school:verify-integrity')->assertExitCode(1);
    }

    public function test_verify_integrity_fails_when_amount_paid_is_altered(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()), '9000.00');
        PaymentService::recordPayment($invoice->id, '4000.00', 'cash', null, today()->toDateString());

        DB::table('invoices')->where('id', $invoice->id)->update(['amount_paid' => '9000.00']);

        $this->artisan('school:verify-integrity')->assertExitCode(1);
    }

    public function test_generate_invoices_creates_n_then_zero(): void
    {
        $class = $this->makeClass();
        $this->makeStudentIn($class);
        $this->makeStudentIn($class);
        FeeStructure::factory()->create(['class_id' => $class->id, 'amount' => '7000.00']);

        $this->artisan('school:generate-invoices', ['class_id' => $class->id])
            ->expectsOutputToContain('Created 2, skipped 0')
            ->assertExitCode(0);

        $this->artisan('school:generate-invoices', ['class_id' => $class->id])
            ->expectsOutputToContain('Created 0, skipped 2')
            ->assertExitCode(0);

        $this->assertSame(2, Invoice::count());
    }

    public function test_generate_invoices_fails_cleanly_without_an_active_fee_structure(): void
    {
        $class = $this->makeClass();
        $this->makeStudentIn($class);
        FeeStructure::factory()->inactive()->create(['class_id' => $class->id]);

        $this->artisan('school:generate-invoices', ['class_id' => $class->id])->assertExitCode(1);

        $this->assertSame(0, Invoice::count());
    }

    public function test_close_term_refuses_while_results_are_unpublished(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 3',
            'sequence' => 3,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);
        TermResult::create([
            'term_id' => $term->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'publication_status' => 'draft',
            'generated_at' => now(),
        ]);

        $this->artisan('school:close-term', ['term_id' => $term->id])->assertExitCode(1);
        $this->assertTrue((bool) $term->fresh()->is_current);

        $this->artisan('school:close-term', ['term_id' => $term->id, '--force' => true])->assertExitCode(0);
        $this->assertFalse((bool) $term->fresh()->is_current);
    }
}
