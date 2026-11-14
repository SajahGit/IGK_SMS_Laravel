<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * Accounts module completion (SOP M19-M21, TC-FIN-xx, TC-RPT-02).
 * Covers the gaps closed in migration 2026_01_01_000029:
 * invoice lines + discount, offsetting reversal record, race-free numbering,
 * invoice cancellation, billable-student / term filters, decimal-scale validation,
 * and the accountant invoice page that used to fail on a missing reverse route.
 */
class AccountsModuleCompletionTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    private User $accountant;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $this->accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'completion-finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $this->accountant->syncRoles(['Accountant']);

        $this->superAdmin = User::create([
            'name' => 'Root Admin',
            'email' => 'completion-root@school.test',
            'password' => 'password',
            'role' => 'super-admin',
        ]);
        $this->superAdmin->syncRoles(['Super Admin']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function fee($class, string $name, string $amount, array $extra = []): FeeStructure
    {
        return FeeStructure::factory()->create(['class_id' => $class->id, 'name' => $name, 'amount' => $amount] + $extra);
    }

    private function issueFor(Student $student, string $amount = '10000.00'): Invoice
    {
        $fee = FeeStructure::factory()->create([
            'class_id' => $student->class_id,
            'name' => 'Fee '.uniqid(),
            'amount' => $amount,
        ]);

        return InvoiceService::generateForStudent(
            $student,
            null,
            today()->toDateString(),
            today()->addDays(30)->toDateString(),
            collect([$fee])
        );
    }

    // ---- invoice lines + discount (SOP §11 invoice_items, Q10) -------------------------

    public function test_invoice_gets_one_line_per_fee_and_a_negative_discount_line(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $tuition = $this->fee($class, 'Tuition', '8000.00');
        $lab = $this->fee($class, 'Lab fee', '2500.00');

        $invoice = InvoiceService::generateForStudent(
            $student, null, today()->toDateString(), today()->addDays(30)->toDateString(),
            collect([$tuition, $lab]), '500.00'
        );

        $this->assertSame('10500.00', $invoice->subtotal);
        $this->assertSame('500.00', $invoice->discount);
        $this->assertSame('10000.00', $invoice->total);
        $this->assertSame('10000.00', $invoice->balance);
        $this->assertSame(3, $invoice->items()->count());
        $this->assertSame('-500.00', $invoice->items()->where('description', 'Discount')->value('amount'));
        $this->assertSame(0, $this->artisanExit('school:verify-integrity'));
    }

    public function test_discount_larger_than_the_amount_billed_is_rejected_and_nothing_is_written(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = $this->fee($class, 'Tuition', '1000.00');

        try {
            InvoiceService::generateForStudent(
                $student, null, today()->toDateString(), today()->addDays(30)->toDateString(),
                collect([$fee]), '1000.01'
            );
            $this->fail('Expected a ValidationException for an oversized discount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('discount', $e->errors());
        }

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, DB::table('invoice_items')->count());
    }

    public function test_bulk_generation_through_the_form_applies_the_discount(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = $this->fee($class, 'Tuition', '12000.00');

        $this->actingAs($this->accountant)->post(route('accountant.invoices.store'), [
            'class_id' => $class->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'fee_structure_ids' => [$fee->id],
            'discount' => '1000.00',
        ])->assertRedirect();

        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('11000.00', $invoice->total);
        $this->assertSame('1000.00', $invoice->discount);
    }

    // ---- who gets billed ---------------------------------------------------------------

    public function test_withdrawn_graduated_and_applicant_students_are_not_billed(): void
    {
        $class = $this->makeClass();
        $active = $this->makeStudentIn($class);
        $withdrawn = $this->makeStudentIn($class);
        $graduated = $this->makeStudentIn($class);
        $applicant = $this->makeStudentIn($class);
        Student::whereKey($withdrawn->id)->update(['status' => 'withdrawn']);
        Student::whereKey($graduated->id)->update(['status' => 'graduated']);
        Student::whereKey($applicant->id)->update(['status' => 'applicant']);
        $fee = $this->fee($class, 'Tuition', '5000.00');

        $result = InvoiceService::bulkGenerateForClass(
            $class->id, null, today()->toDateString(), today()->addDays(30)->toDateString(), [$fee->id]
        );

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['total']);
        $this->assertSame([$active->id], Invoice::pluck('student_id')->all());
    }

    public function test_a_fee_structure_for_another_term_is_not_applied(): void
    {
        $class = $this->makeClass();
        $this->makeStudentIn($class);

        $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true]);
        $term1 = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-05', 'end_date' => '2026-04-30', 'is_current' => false]);
        $term2 = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'sequence' => 2, 'start_date' => '2026-05-05', 'end_date' => '2026-08-30', 'is_current' => true]);
        $term1Fee = $this->fee($class, 'Term 1 tuition', '7000.00', ['term_id' => $term1->id]);
        $allTermsFee = $this->fee($class, 'Uniform', '1000.00');

        try {
            InvoiceService::bulkGenerateForClass($class->id, $term2->id, today()->toDateString(), today()->addDays(30)->toDateString(), [$term1Fee->id]);
            $this->fail('A Term 1 fee must not be billed against Term 2.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fee_structure_ids', $e->errors());
        }

        InvoiceService::bulkGenerateForClass($class->id, $term2->id, today()->toDateString(), today()->addDays(30)->toDateString(), [$term1Fee->id, $allTermsFee->id]);

        $this->assertSame('1000.00', Invoice::firstOrFail()->total);
    }

    // ---- numbering (M21 criterion 3) ---------------------------------------------------

    public function test_receipt_numbers_across_different_invoices_come_from_one_counter(): void
    {
        $class = $this->makeClass();
        $invoiceA = $this->issueFor($this->makeStudentIn($class));
        $invoiceB = $this->issueFor($this->makeStudentIn($class));

        $first = PaymentService::recordPayment($invoiceA->id, '100.00', 'cash', null, today()->toDateString());
        $second = PaymentService::recordPayment($invoiceB->id, '100.00', 'cash', null, today()->toDateString());

        $this->assertSame('RCP-'.date('Y').'-000001', $first->receipt_no);
        $this->assertSame('RCP-'.date('Y').'-000002', $second->receipt_no);
        $this->assertSame(2, (int) DB::table('number_sequences')->where('name', 'receipt:'.date('Y'))->value('last_value'));
    }

    public function test_a_rolled_back_payment_does_not_burn_a_receipt_number(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()));

        try {
            DB::transaction(function () use ($invoice) {
                PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, today()->toDateString());
                throw new RuntimeException('Forced failure after the payment was written.');
            });
            $this->fail('The forced failure should have propagated.');
        } catch (RuntimeException $e) {
            $this->assertSame('Forced failure after the payment was written.', $e->getMessage());
        }

        $this->assertSame(0, Payment::count());

        $payment = PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, today()->toDateString());
        $this->assertSame('RCP-'.date('Y').'-000001', $payment->receipt_no);
    }

    // ---- payments ----------------------------------------------------------------------

    public function test_payment_with_more_than_two_decimals_is_rejected_by_service_and_form(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()));

        try {
            PaymentService::recordPayment($invoice->id, '100.005', 'cash', null, today()->toDateString());
            $this->fail('A three-decimal amount must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->actingAs($this->accountant)->post(route('accountant.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => '100.005',
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::count());
        $this->assertSame('10000.00', $invoice->fresh()->balance);
    }

    public function test_every_payment_and_reversal_writes_an_audit_row(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()));

        $payment = PaymentService::recordPayment($invoice->id, '250.00', 'cash', null, today()->toDateString());
        $this->assertSame(1, AuditLog::where('action', 'payment_received')->where('subject_id', $payment->id)->count());

        PaymentService::reversePayment($payment, 'Entered on the wrong invoice');
        $this->assertSame(1, AuditLog::where('action', 'payment_reversed')->where('subject_id', $payment->id)->count());
    }

    // ---- reversal = offsetting record, never a delete (PR-5, TC-FIN-18) ----------------

    public function test_reversal_keeps_the_payment_and_writes_an_offsetting_record(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '500.00');
        $payment = PaymentService::recordPayment($invoice->id, '500.00', 'cash', null, today()->toDateString());
        $this->assertSame('paid', $invoice->fresh()->status);

        PaymentService::reversePayment($payment, 'Bounced cheque', $this->superAdmin->id);

        $this->assertSame(1, Payment::count(), 'The original payment must be retained.');
        $this->assertDatabaseHas('payment_reversals', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => '500.00',
            'reason' => 'Bounced cheque',
            'reversed_by' => $this->superAdmin->id,
        ]);

        $invoice->refresh();
        $this->assertSame('500.00', $invoice->balance);
        $this->assertSame('0.00', $invoice->amount_paid);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame(0, $this->artisanExit('school:verify-integrity'));

        try {
            PaymentService::reversePayment($payment, 'Second attempt');
            $this->fail('A payment can only be reversed once.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reversal', $e->errors());
        }
        $this->assertSame(1, DB::table('payment_reversals')->count());
    }

    public function test_reversal_requires_a_reason(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '500.00');
        $payment = PaymentService::recordPayment($invoice->id, '500.00', 'cash', null, today()->toDateString());

        try {
            PaymentService::reversePayment($payment, '   ');
            $this->fail('A blank reason must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reversal_reason', $e->errors());
        }

        $this->assertFalse($payment->fresh()->isReversed());
    }

    public function test_only_a_super_admin_can_reverse_a_payment_over_http(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '500.00');
        $payment = PaymentService::recordPayment($invoice->id, '500.00', 'cash', null, today()->toDateString());

        $institutionAdmin = User::create([
            'name' => 'Institution Admin',
            'email' => 'completion-inst-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $institutionAdmin->syncRoles(['Institution Admin']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($institutionAdmin)
            ->post(route('admin.payments.reverse', $payment), ['reversal_reason' => 'Trying to reverse'])
            ->assertForbidden();
        $this->assertFalse($payment->fresh()->isReversed());

        $this->actingAs($this->superAdmin)
            ->post(route('admin.payments.reverse', $payment), ['reversal_reason' => 'Dishonoured cheque'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($payment->fresh()->isReversed());
        $this->assertSame('500.00', $invoice->fresh()->balance);
    }

    // ---- the page that used to crash ---------------------------------------------------

    public function test_accountant_can_open_an_invoice_that_already_has_a_payment(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '3000.00');

        $this->actingAs($this->accountant);
        $payment = PaymentService::recordPayment($invoice->id, '1000.00', 'cash', null, today()->toDateString());

        // The accountant area has no reversal route; the page must render without a Reverse button.
        $this->get(route('accountant.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($payment->receipt_no)
            ->assertSee('Billed Items')
            ->assertDontSee('data-receipt="'.$payment->receipt_no.'"', false);
    }

    public function test_super_admin_sees_the_reverse_button_on_the_admin_invoice_page(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '3000.00');
        $payment = PaymentService::recordPayment($invoice->id, '1000.00', 'cash', null, today()->toDateString());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('data-receipt="'.$payment->receipt_no.'"', false);
    }

    // ---- cancellation ------------------------------------------------------------------

    public function test_unpaid_invoice_can_be_cancelled_with_a_reason_and_then_takes_no_payments(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '4000.00');

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.cancel', $invoice), ['cancellation_reason' => 'Billed to the wrong class'])
            ->assertRedirect(route('accountant.invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertSame('Billed to the wrong class', $invoice->cancellation_reason);
        $this->assertSame(1, AuditLog::where('action', 'invoice_cancelled')->where('subject_id', $invoice->id)->count());

        try {
            PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, today()->toDateString());
            $this->fail('A cancelled invoice must not accept payments.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }

    public function test_cancel_needs_a_reason_and_is_refused_while_payments_exist(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '4000.00');

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.cancel', $invoice), [])
            ->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('issued', $invoice->fresh()->status);

        $payment = PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, today()->toDateString());

        $this->post(route('accountant.invoices.cancel', $invoice), ['cancellation_reason' => 'Trying to cancel'])
            ->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('partially_paid', $invoice->fresh()->status);

        // After the payment is reversed the invoice can be cancelled.
        PaymentService::reversePayment($payment, 'Customer changed mind');
        $this->post(route('accountant.invoices.cancel', $invoice), ['cancellation_reason' => 'Now it is allowed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $invoice->fresh()->status);
    }

    // ---- fee structures ----------------------------------------------------------------

    public function test_duplicate_fee_name_for_the_same_class_and_term_is_rejected(): void
    {
        $class = $this->makeClass();
        $payload = ['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '9000.00', 'is_active' => 1];

        $this->actingAs($this->accountant)->post(route('accountant.fee-structures.store'), $payload)->assertRedirect();
        $this->post(route('accountant.fee-structures.store'), $payload)->assertSessionHasErrors('name');

        $this->assertSame(1, FeeStructure::count());
    }

    public function test_fee_amount_with_three_decimals_is_rejected(): void
    {
        $class = $this->makeClass();

        $this->actingAs($this->accountant)->post(route('accountant.fee-structures.store'), [
            'class_id' => $class->id, 'name' => 'Tuition', 'amount' => '9000.005', 'is_active' => 1,
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, FeeStructure::count());
    }

    public function test_fee_name_format_and_positive_amount_are_enforced(): void
    {
        $class = $this->makeClass();
        $base = ['class_id' => $class->id, 'amount' => '10.00', 'is_active' => 1];

        $this->actingAs($this->accountant)
            ->post(route('accountant.fee-structures.store'), $base + ['name' => '2026 / Tuition'])
            ->assertSessionHasErrors('name');

        $this->post(route('accountant.fee-structures.store'), ['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '0.00'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, FeeStructure::count());
    }

    // ---- dashboards / totals -----------------------------------------------------------

    public function test_dashboard_totals_ignore_reversed_payments_and_cancelled_invoices(): void
    {
        $class = $this->makeClass();
        $kept = $this->issueFor($this->makeStudentIn($class), '5000.00');
        $cancelled = $this->issueFor($this->makeStudentIn($class), '7000.00');
        $reversedOne = $this->issueFor($this->makeStudentIn($class), '2000.00');

        PaymentService::recordPayment($kept->id, '1500.00', 'cash', null, today()->toDateString());
        $toReverse = PaymentService::recordPayment($reversedOne->id, '2000.00', 'cash', null, today()->toDateString());
        PaymentService::reversePayment($toReverse, 'Duplicate entry');
        InvoiceService::cancel($cancelled, 'Raised in error');

        $this->actingAs($this->accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertViewHas('invoiceCount', 2)
            ->assertViewHas('totalBilled', fn (array $amounts) => count($amounts) === 1 && (float) reset($amounts) === 7000.0)
            ->assertViewHas('totalCollected', fn (array $amounts) => count($amounts) === 1 && (float) reset($amounts) === 1500.0)
            ->assertViewHas('totalOutstanding', fn (array $amounts) => count($amounts) === 1 && (float) reset($amounts) === 5500.0);
    }

    // ---- integrity ---------------------------------------------------------------------

    public function test_verify_integrity_flags_tampered_invoice_lines(): void
    {
        $invoice = $this->issueFor($this->makeStudentIn($this->makeClass()), '6000.00');
        $this->assertSame(0, $this->artisanExit('school:verify-integrity'));

        DB::table('invoice_items')->where('invoice_id', $invoice->id)->update(['amount' => '1.00']);

        $this->assertSame(1, $this->artisanExit('school:verify-integrity'));
    }

    // ---- access (TC-RPT-02, PR-2) ------------------------------------------------------

    public function test_teacher_gets_403_on_the_financial_report_and_its_csv(): void
    {
        $teacher = User::create([
            'name' => 'Teacher',
            'email' => 'completion-teacher@school.test',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $teacher->syncRoles(['Teacher']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($teacher)->get(route('reports.fee-collection'))->assertForbidden();
        $this->get(route('reports.fee-collection.csv'))->assertForbidden();
        $this->get(route('reports.defaulters'))->assertForbidden();
        $this->get(route('reports.defaulters.csv'))->assertForbidden();
    }

    public function test_student_can_download_own_invoice_pdf_but_not_someone_elses(): void
    {
        $class = $this->makeClass();
        $mine = $this->makeStudentIn($class);
        $other = $this->makeStudentIn($class);
        $myInvoice = $this->issueFor($mine, '1200.00');
        $otherInvoice = $this->issueFor($other, '1300.00');

        $this->actingAs(User::findOrFail($mine->user_id))->get(route('student.invoices.pdf', $myInvoice))->assertOk();
        $this->get(route('student.invoices.pdf', $otherInvoice))->assertNotFound();
    }

    private function artisanExit(string $command): int
    {
        return Artisan::call($command);
    }
}
