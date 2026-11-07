<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\PaymentReceivedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP §18.2 workflow test #2:
 * fee structure -> invoice -> payment -> receipt -> balance report, and the figures agree.
 */
class FinanceEndToEndWorkflowTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    public function test_fee_structure_to_invoice_to_payment_to_receipt_to_report_figures_agree(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'e2e-finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $accountant->syncRoles(['Accountant']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $class = $this->makeClass('Grade 9', 'C');
        $student = $this->makeStudentIn($class);
        $this->actingAs($accountant);

        // 1. Fee structures
        foreach ([['Tuition', '10000.00'], ['Lab', '2500.00']] as [$name, $amount]) {
            $this->post(route('accountant.fee-structures.store'), [
                'class_id' => $class->id,
                'name' => $name,
                'amount' => $amount,
                'is_active' => 1,
            ])->assertRedirect();
        }
        $this->assertSame(2, FeeStructure::where('class_id', $class->id)->count());

        // 2. Invoice for the class
        $this->post(route('accountant.invoices.store'), [
            'class_id' => $class->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'fee_structure_ids' => FeeStructure::where('class_id', $class->id)->pluck('id')->all(),
        ])->assertRedirect();

        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('12500.00', $invoice->total);
        $this->assertSame('12500.00', $invoice->balance);

        // 3. Partial payment, then 4. receipt page and PDF
        $this->post(route('accountant.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => '5000.00',
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('5000.00', $invoice->amount_paid);
        $this->assertSame('7500.00', $invoice->balance);
        $this->assertSame('partially_paid', $invoice->status);

        $first = $invoice->payments()->firstOrFail();
        $this->get(route('accountant.receipt.show', $first))->assertOk()->assertSee($first->receipt_no);
        $this->get(route('accountant.receipt.pdf', $first))->assertOk()->assertHeader('content-type', 'application/pdf');

        // 5. Report agrees with the payment
        $report = route('accountant.reports.fee-collection', ['from' => today()->toDateString(), 'to' => today()->toDateString()]);
        $this->get($report)->assertOk()->assertSee('LKR 5,000.00');

        // Pay the rest: invoice is paid, report total is the whole invoice.
        $this->post(route('accountant.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => '7500.00',
            'method' => 'bank_transfer',
            'reference' => 'TX-E2E-1',
            'paid_at' => today()->toDateString(),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('12500.00', $invoice->amount_paid);
        $this->assertSame('0.00', $invoice->balance);
        $this->assertSame('paid', $invoice->status);

        $this->get($report)->assertOk()->assertSee('LKR 12,500.00');
        $this->get(route('accountant.reports.defaulters'))->assertOk();
        $this->get(route('accountant.reports.student-ledger'))->assertOk();

        // The student was told about the invoice and both receipts.
        $user = $student->user;
        $this->assertSame(1, $user->notifications()->where('type', InvoiceIssuedNotification::class)->count());
        $this->assertSame(2, $user->notifications()->where('type', PaymentReceivedNotification::class)->count());

        // Stored balances still agree with the payments.
        $this->artisan('school:verify-integrity')->assertExitCode(0);
    }
}
