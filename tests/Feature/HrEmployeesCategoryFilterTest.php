<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\User;
use App\Services\EmployeeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HrEmployeesCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Branch $branch;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);

        $this->branch = Branch::create(['name' => 'Main Campus', 'code' => 'MAIN', 'is_main' => true, 'is_active' => true]);
        $this->department = Department::create(['name' => 'Academics', 'code' => 'ACA', 'branch_id' => $this->branch->id, 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'hr.cat.admin@school.test',
            'password' => 'AdminPass123!',
            'role' => 'super-admin',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->superAdmin->syncRoles(['Super Admin']);
        EmployeeSyncService::ensureForStaffUser($this->superAdmin);
    }

    #[Test]
    public function creating_teacher_and_staff_users_automatically_registers_them_in_hr_employees(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.teachers.store'), [
                'name' => 'Auto Sync Teacher',
                'email' => 'autosync.teacher@school.test',
                'password' => 'StrongTeacher99!',
                'department_id' => $this->department->id,
                'branch_id' => $this->branch->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.teachers.index'));

        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Auto Sync Accountant',
                'email' => 'autosync.accountant@school.test',
                'password' => 'StrongAcc99!',
                'roles' => ['Accountant'],
                'branch_id' => $this->branch->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Auto Sync Librarian',
                'email' => 'autosync.librarian@school.test',
                'password' => 'StrongLib99!',
                'roles' => ['Librarian'],
                'branch_id' => $this->branch->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('employees', ['job_title' => 'Teacher']);
        $this->assertDatabaseHas('employees', ['job_title' => 'Accountant']);
        $this->assertDatabaseHas('employees', ['job_title' => 'Librarian']);

        // Index shows all employees across categories
        $this->actingAs($this->superAdmin)
            ->get(route('admin.hr.employees.index'))
            ->assertOk()
            ->assertSee('Auto Sync Teacher')
            ->assertSee('Auto Sync Accountant')
            ->assertSee('Auto Sync Librarian')
            ->assertSee('Teaching Faculty')
            ->assertSee('Finance & Accounts')
            ->assertSee('Library & Operations');

        // Filter by teaching category shows teacher, hides accountant
        $this->actingAs($this->superAdmin)
            ->get(route('admin.hr.employees.index', ['category' => 'teaching']))
            ->assertOk()
            ->assertSee('Auto Sync Teacher')
            ->assertDontSee('Auto Sync Accountant');

        // Filter by finance category shows accountant, hides teacher
        $this->actingAs($this->superAdmin)
            ->get(route('admin.hr.employees.index', ['category' => 'finance']))
            ->assertOk()
            ->assertSee('Auto Sync Accountant')
            ->assertDontSee('Auto Sync Teacher');
    }

    #[Test]
    public function sync_all_missing_backfills_unlinked_staff_without_duplicating_existing_employees(): void
    {
        $unlinkedUser = User::create([
            'name' => 'Unlinked Registrar',
            'email' => 'unlinked.reg@school.test',
            'password' => 'RegPass123!',
            'role' => 'registrar',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $unlinkedTeacherUser = User::create([
            'name' => 'Unlinked Teacher',
            'email' => 'unlinked.t@school.test',
            'password' => 'TeacherPass123!',
            'role' => 'teacher',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $unlinkedTeacher = Teacher::create([
            'user_id' => $unlinkedTeacherUser->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        $createdCount = EmployeeSyncService::syncAllMissing();
        $this->assertGreaterThanOrEqual(2, $createdCount);

        // Running again is idempotent (0 duplicates)
        $this->assertSame(0, EmployeeSyncService::syncAllMissing());
        $this->assertTrue(Employee::where('user_id', $unlinkedUser->id)->exists());
        $this->assertTrue(Employee::where('teacher_id', $unlinkedTeacher->id)->exists());
    }
}
