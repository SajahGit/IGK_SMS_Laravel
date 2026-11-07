<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase4FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bcmath_money_helper_precision(): void
    {
        $this->assertEquals('150.75', Money::add('100.50', '50.25'));
        $this->assertEquals('49.75', Money::sub('100.00', '50.25'));
        $this->assertEquals('300.00', Money::mul('100.00', '3'));
        $this->assertEquals('33.33', Money::div('100.00', '3'));
        $this->assertEquals(0, Money::comp('100.00', '100.00'));
        $this->assertEquals(1, Money::comp('100.01', '100.00'));
        $this->assertEquals(-1, Money::comp('99.99', '100.00'));
        $this->assertEquals('LKR 1,500.50', Money::format('1500.50'));
    }

    public function test_bulk_invoice_generation_idempotency_and_sequential_numbering(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $u1 = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $s1 = Student::create(['user_id' => $u1->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $u2 = User::create(['name' => 'S2', 'email' => 's2@s.t', 'password' => 'password', 'role' => 'student']);
        $s2 = Student::create(['user_id' => $u2->id, 'class_id' => $class->id, 'admission_no' => 'ADM002']);

        $fee = FeeStructure::create([
            'class_id' => $class->id,
            'name' => 'Tuition Fee',
            'amount' => '10000.00',
            'is_active' => true,
        ]);

        $res = InvoiceService::bulkGenerateForClass(
            $class->id,
            null,
            '2026-05-01',
            '2026-05-31',
            [$fee->id]
        );

        $this->assertEquals(2, $res['created']);
        $this->assertEquals(0, $res['skipped']);

        $invoices = Invoice::orderBy('invoice_no')->get();
        $this->assertEquals(2, $invoices->count());
        $this->assertEquals('10000.00', $invoices[0]->total);
        $this->assertEquals('10000.00', $invoices[0]->balance);
        $this->assertStringStartsWith('INV-'.date('Y').'-', $invoices[0]->invoice_no);

        // Re-running same bulk generation skips both students (idempotent)
        $resSecond = InvoiceService::bulkGenerateForClass(
            $class->id,
            null,
            '2026-05-01',
            '2026-05-31',
            [$fee->id]
        );

        $this->assertEquals(0, $resSecond['created']);
        $this->assertEquals(2, $resSecond['skipped']);
        $this->assertEquals(2, Invoice::count());
    }

    public function test_payment_recording_and_overpayment_rejection(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $u = User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $u->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'INV-2026-0001',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'total' => '10000.00',
            'amount_paid' => '0.00',
            'balance' => '10000.00',
            'status' => 'issued',
        ]);

        // Attempting to pay 10000.01 exceeds balance -> throws validation exception
        try {
            PaymentService::recordPayment($invoice->id, '10000.01', 'cash', null, '2026-05-05');
            $this->fail('Expected ValidationException for overpayment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Partial payment of 6000.00
        $p1 = PaymentService::recordPayment($invoice->id, '6000.00', 'cash', null, '2026-05-05');
        $this->assertStringStartsWith('RCP-'.date('Y').'-', $p1->receipt_no);

        $invoice->refresh();
        $this->assertEquals('6000.00', $invoice->amount_paid);
        $this->assertEquals('4000.00', $invoice->balance);
        $this->assertEquals('partially_paid', $invoice->status);

        // Final payment of remaining 4000.00
        PaymentService::recordPayment($invoice->id, '4000.00', 'bank_transfer', 'TX-999', '2026-05-10');

        $invoice->refresh();
        $this->assertEquals('10000.00', $invoice->amount_paid);
        $this->assertEquals('0.00', $invoice->balance);
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_payment_reversal_restores_invoice_balance(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $u = User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $u->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'INV-2026-0002',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'total' => '5000.00',
            'amount_paid' => '0.00',
            'balance' => '5000.00',
            'status' => 'issued',
        ]);

        $payment = PaymentService::recordPayment($invoice->id, '5000.00', 'cash', null, '2026-05-05');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('0.00', $invoice->balance);

        // Reverse payment
        PaymentService::reversePayment($payment, 'Dishonoured cheque');

        $payment->refresh();
        $invoice->refresh();

        $this->assertTrue($payment->isReversed());
        $this->assertEquals('Dishonoured cheque', $payment->reversal_reason);
        $this->assertEquals('0.00', $invoice->amount_paid);
        $this->assertEquals('5000.00', $invoice->balance);
        $this->assertEquals('issued', $invoice->status);
    }
}
