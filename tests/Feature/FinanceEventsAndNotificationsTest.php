<?php

namespace Tests\Feature;

use App\Events\InvoiceIssued;
use App\Events\PaymentRecorded;
use App\Listeners\SendReceiptEmail;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP §10.1 Events / Listeners / Notifications for invoices and payments.
 */
class FinanceEventsAndNotificationsTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    public function test_bulk_generation_dispatches_invoice_issued_per_new_invoice(): void
    {
        Event::fake([InvoiceIssued::class]);

        $class = $this->makeClass();
        $this->makeStudentIn($class);
        $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id]);

        InvoiceService::bulkGenerateForClass($class->id, null, today()->toDateString(), today()->addDays(30)->toDateString(), [$fee->id]);
        InvoiceService::bulkGenerateForClass($class->id, null, today()->toDateString(), today()->addDays(30)->toDateString(), [$fee->id]);

        // The second (idempotent) run issues nothing, so only two events in total.
        Event::assertDispatchedTimes(InvoiceIssued::class, 2);
    }

    public function test_recording_a_payment_dispatches_payment_recorded(): void
    {
        Event::fake([PaymentRecorded::class]);

        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()));
        $payment = PaymentService::recordPayment($invoice->id, '2500.00', 'cash', null, today()->toDateString());

        Event::assertDispatched(PaymentRecorded::class, fn (PaymentRecorded $event) => $event->payment->is($payment));
    }

    public function test_student_receives_invoice_and_receipt_notifications(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'amount' => '8000.00']);

        InvoiceService::bulkGenerateForClass($class->id, null, today()->toDateString(), today()->addDays(30)->toDateString(), [$fee->id]);

        $user = $student->user;
        $this->assertSame(1, $user->notifications()->where('type', InvoiceIssuedNotification::class)->count());

        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
        PaymentService::recordPayment($invoice->id, '3000.00', 'cash', null, today()->toDateString());

        $this->assertSame(1, $user->notifications()->where('type', PaymentReceivedNotification::class)->count());
    }

    public function test_listener_is_idempotent(): void
    {
        $student = $this->makeStudentIn($this->makeClass());
        $invoice = $this->makeInvoiceFor($student);
        $payment = PaymentService::recordPayment($invoice->id, '1000.00', 'cash', null, today()->toDateString());

        // Handle the same event twice (a queue retry): still exactly one receipt notification.
        (new SendReceiptEmail)->handle(new PaymentRecorded($payment));
        (new SendReceiptEmail)->handle(new PaymentRecorded($payment));

        $this->assertSame(1, $student->user->notifications()->where('type', PaymentReceivedNotification::class)->count());
    }

    public function test_a_failing_listener_never_rolls_back_the_payment(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()), '5000.00');

        Event::listen(PaymentRecorded::class, function () {
            throw new RuntimeException('Mail server is down.');
        });

        try {
            PaymentService::recordPayment($invoice->id, '5000.00', 'cash', null, today()->toDateString());
        } catch (RuntimeException) {
            // The failure may surface to the caller; what matters is that the money write stays.
        }

        $this->assertSame(1, Payment::count());
        $invoice->refresh();
        $this->assertSame('5000.00', $invoice->amount_paid);
        $this->assertSame('0.00', $invoice->balance);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_zero_or_negative_payment_is_rejected_and_writes_nothing(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()));

        foreach (['0.00', '-50.00'] as $amount) {
            try {
                PaymentService::recordPayment($invoice->id, $amount, 'cash', null, today()->toDateString());
                $this->fail("Expected ValidationException for amount {$amount}.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('amount', $e->errors());
            }
        }

        $this->assertSame(0, Payment::count());
        $this->assertSame('10000.00', $invoice->fresh()->balance);
    }
}
