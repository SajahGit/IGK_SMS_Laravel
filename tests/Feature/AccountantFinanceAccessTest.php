<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountantFinanceAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $this->accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $this->accountant->syncRoles(['Accountant']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_accountant_dashboard_and_finance_pages_render_from_accountant_area(): void
    {
        $this->actingAs($this->accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSee('Finance Dashboard');

        $this->get(route('accountant.fee-structures.index'))->assertOk();
        $this->get(route('accountant.invoices.index'))->assertOk();
        $this->get(route('accountant.invoices.create'))->assertOk();
        $this->get(route('accountant.payments.create'))->assertOk();
    }

    public function test_accountant_dashboard_notification_dropdown_handles_unread_notifications(): void
    {
        $this->accountant->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['title' => 'Notification regression check', 'body' => 'Unread notification body'];
            }
        });

        $this->actingAs($this->accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSee('Notification regression check')
            ->assertSee('Unread notification body')
            ->assertSee('>1</span>', false);
    }

    public function test_legacy_accountant_role_value_is_normalized_for_dashboard_access(): void
    {
        $legacyAccountant = User::create([
            'name' => 'Legacy Finance Officer',
            'email' => 'legacy-finance@school.test',
            'password' => 'password',
            'role' => 'Accountant',
        ]);

        $this->assertSame('accountant.dashboard', $legacyAccountant->dashboardRoute());

        $this->actingAs($legacyAccountant)
            ->get(route('accountant.dashboard'))
            ->assertOk();
    }

    public function test_accountant_can_create_fee_structure_and_issue_invoice(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A', 'capacity' => 40]);
        $studentUser = User::create([
            'name' => 'Student',
            'email' => 'student@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        $this->actingAs($this->accountant)->post(route('accountant.fee-structures.store'), [
            'class_id' => $class->id,
            'name' => 'Term Tuition',
            'amount' => '12500.00',
            'is_active' => 1,
        ])->assertRedirect(route('accountant.fee-structures.index', ['class_id' => $class->id]));

        $fee = FeeStructure::firstOrFail();
        $this->post(route('accountant.invoices.store'), [
            'class_id' => $class->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'fee_structure_ids' => [$fee->id],
        ])->assertRedirect(route('accountant.invoices.index', ['class_id' => $class->id]));

        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'total' => '12500.00',
            'balance' => '12500.00',
        ]);
    }

    public function test_accountant_can_record_partial_payment_and_open_receipt(): void
    {
        $student = $this->createStudentInvoice();

        $this->actingAs($this->accountant)->post(route('accountant.payments.store'), [
            'invoice_id' => $student->invoices()->first()->id,
            'amount' => '2500.00',
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('2500.00', $invoice->amount_paid);
        $this->assertSame('7500.00', $invoice->balance);
        $this->assertSame('partially_paid', $invoice->status);

        $payment = $invoice->payments()->firstOrFail();
        $this->get(route('accountant.receipt.show', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_no);
        $this->get(route('accountant.receipt.pdf', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_accountant_can_access_financial_reports_but_not_academic_admin_or_reversal_routes(): void
    {
        $this->actingAs($this->accountant);

        $this->get(route('accountant.reports.fee-collection'))->assertOk();
        $this->get(route('accountant.reports.defaulters'))->assertOk();
        $this->get(route('accountant.reports.student-ledger'))->assertOk();
        $this->get(route('accountant.reports.fee-collection.csv'))->assertOk();

        $this->get(route('reports.enrollment'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('accountant.payments.reverse'));
    }

    private function createStudentInvoice(): Student
    {
        $class = SchoolClass::create(['name' => 'Grade 11', 'section' => 'B', 'capacity' => 40]);
        $studentUser = User::create([
            'name' => 'Student',
            'email' => 'student2@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM002',
        ]);
        Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'INV-2026-0001',
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'total' => '10000.00',
            'amount_paid' => '0.00',
            'balance' => '10000.00',
            'status' => 'issued',
        ]);

        return $student;
    }
}
