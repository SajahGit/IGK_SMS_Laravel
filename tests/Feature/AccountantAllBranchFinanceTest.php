<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountantAllBranchFinanceTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private Branch $mainBranch;
    private Branch $kandyBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $this->mainBranch = Branch::create([
            'code' => 'MAIN',
            'name' => 'IGK Main Campus',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->kandyBranch = Branch::create([
            'code' => 'KANDY',
            'name' => 'IGK Kandy Campus',
            'is_main' => false,
            'is_active' => true,
        ]);

        $this->accountant = User::create([
            'name' => 'Daniel Osei',
            'email' => 'accountant@school.test',
            'password' => 'password',
            'role' => 'accountant',
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->accountant->syncRoles(['Accountant']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_accountant_can_switch_branch_context_to_specific_campus_and_all_campuses(): void
    {
        $this->actingAs($this->accountant);

        // Switch to Kandy Campus
        $response = $this->post(route('branch-context'), [
            'branch_id' => $this->kandyBranch->id,
        ]);
        $response->assertRedirect();
        $this->assertSame($this->kandyBranch->id, session('active_branch_id'));

        // Switch to All Campuses
        $response = $this->post(route('branch-context'), [
            'branch_id' => 'all',
        ]);
        $response->assertRedirect();
        $this->assertSame('all', session('active_branch_id'));

        // Admin alias also functions identically
        $response = $this->post(route('admin.branch-context'), [
            'branch_id' => $this->mainBranch->id,
        ]);
        $response->assertRedirect();
        $this->assertSame($this->mainBranch->id, session('active_branch_id'));
    }

    public function test_accountant_sees_invoices_across_all_campuses_and_scoped_per_campus(): void
    {
        // Student in Main Campus
        $classMain = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'capacity' => 30,
            'branch_id' => $this->mainBranch->id,
        ]);
        $userMain = User::create([
            'name' => 'Main Student',
            'email' => 'student.main@school.test',
            'password' => 'password',
            'role' => 'student',
            'branch_id' => $this->mainBranch->id,
        ]);
        $studentMain = Student::create([
            'user_id' => $userMain->id,
            'class_id' => $classMain->id,
            'admission_no' => 'ADM-MAIN-001',
            'branch_id' => $this->mainBranch->id,
        ]);
        $invMain = Invoice::create([
            'student_id' => $studentMain->id,
            'branch_id' => $this->mainBranch->id,
            'invoice_no' => 'INV-MAIN-001',
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'total' => '10000.00',
            'balance' => '10000.00',
            'status' => 'issued',
        ]);

        // Student in Kandy Campus
        $classKandy = SchoolClass::create([
            'name' => 'Grade 11',
            'section' => 'B',
            'capacity' => 30,
            'branch_id' => $this->kandyBranch->id,
        ]);
        $userKandy = User::create([
            'name' => 'Kandy Student',
            'email' => 'student.kandy@school.test',
            'password' => 'password',
            'role' => 'student',
            'branch_id' => $this->kandyBranch->id,
        ]);
        $studentKandy = Student::create([
            'user_id' => $userKandy->id,
            'class_id' => $classKandy->id,
            'admission_no' => 'ADM-KDY-001',
            'branch_id' => $this->kandyBranch->id,
        ]);
        $invKandy = Invoice::create([
            'student_id' => $studentKandy->id,
            'branch_id' => $this->kandyBranch->id,
            'invoice_no' => 'INV-KDY-001',
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'total' => '15000.00',
            'balance' => '15000.00',
            'status' => 'issued',
        ]);

        $this->actingAs($this->accountant);

        // 1. In 'all' campuses view: both invoices visible
        session(['active_branch_id' => 'all']);
        $this->get(route('accountant.invoices.index'))
            ->assertOk()
            ->assertSee('INV-MAIN-001')
            ->assertSee('INV-KDY-001');

        // 2. In Kandy campus view: only Kandy invoice visible
        session(['active_branch_id' => $this->kandyBranch->id]);
        $this->get(route('accountant.invoices.index'))
            ->assertOk()
            ->assertSee('INV-KDY-001')
            ->assertDontSee('INV-MAIN-001');

        // 3. In Main campus view: only Main invoice visible
        session(['active_branch_id' => $this->mainBranch->id]);
        $this->get(route('accountant.invoices.index'))
            ->assertOk()
            ->assertSee('INV-MAIN-001')
            ->assertDontSee('INV-KDY-001');
    }

    public function test_accountant_can_access_accounting_ledgers_and_branch_summary_but_not_branch_crud(): void
    {
        $this->actingAs($this->accountant);

        // Multi-branch accounting routes
        $this->get(route('admin.accounting.branch-summary'))->assertOk();
        $this->get(route('admin.accounts.index'))->assertOk();
        $this->get(route('admin.journals.index'))->assertOk();
        $this->get(route('admin.accounting.trial-balance'))->assertOk();
        $this->get(route('admin.accounting.ledger'))->assertOk();
        $this->get(route('admin.accounting.income-expense'))->assertOk();

        // Branch management CRUD must be forbidden for accountant
        $this->get(route('admin.branches.index'))->assertForbidden();
        $this->get(route('admin.branches.create'))->assertForbidden();

        // Admin dashboard must remain forbidden for accountant
        $this->get(route('admin.dashboard'))->assertForbidden();
    }
}
