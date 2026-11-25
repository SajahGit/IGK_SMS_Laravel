<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePersonSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Branch $branchA;
    private Branch $branchB;
    private Department $deptA;
    private Department $deptB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);

        $this->branchA = Branch::create(['name' => 'Campus A', 'code' => 'CA', 'is_main' => true, 'is_active' => true]);
        $this->branchB = Branch::create(['name' => 'Campus B', 'code' => 'CB', 'is_main' => false, 'is_active' => true]);
        $this->deptA = Department::create(['name' => 'Science', 'code' => 'SCI', 'branch_id' => $this->branchA->id, 'is_active' => true]);
        $this->deptB = Department::create(['name' => 'Math', 'code' => 'MTH', 'branch_id' => $this->branchB->id, 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'sync.super@school.test',
            'password' => 'AdminPass123!',
            'role' => 'super-admin',
            'is_active' => true,
        ]);
        $this->superAdmin->syncRoles(['Super Admin']);
    }

    #[Test]
    public function updating_user_syncs_linked_teacher_employee_and_staff_attendance(): void
    {
        $teacherUser = User::create([
            'name' => 'Old Teacher Name',
            'email' => 'teacher.sync@school.test',
            'password' => 'TeacherPass123!',
            'role' => 'teacher',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $teacherUser->syncRoles(['Teacher']);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-SYNC-01',
            'user_id' => $teacherUser->id,
            'teacher_id' => $teacher->id,
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branchA->id,
            'job_title' => 'Teacher',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'base_salary' => 90000.00,
        ]);

        $attendance = StaffAttendance::create([
            'teacher_id' => $teacher->id,
            'employee_id' => $employee->id,
            'branch_id' => $this->branchA->id,
            'staff_name' => 'Old Teacher Name',
            'attendance_date' => today()->toDateString(),
            'check_in' => '08:00:00',
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.update', $teacherUser), [
                'name' => 'New Teacher Name',
                'email' => 'teacher.sync@school.test',
                'roles' => ['Teacher'],
                'branch_id' => $this->branchB->id,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('New Teacher Name', $teacherUser->fresh()->name);
        $this->assertFalse($teacherUser->fresh()->is_active);
        $this->assertSame($this->branchB->id, $teacher->fresh()->branch_id);
        $this->assertSame('inactive', $teacher->fresh()->status);
        $this->assertSame($this->branchB->id, $employee->fresh()->branch_id);
        $this->assertSame('terminated', $employee->fresh()->employment_status);
        $this->assertSame('New Teacher Name', $attendance->fresh()->staff_name);
    }

    #[Test]
    public function updating_teacher_syncs_linked_user_and_employee(): void
    {
        $teacherUser = User::create([
            'name' => 'Teacher Two',
            'email' => 'teacher2.sync@school.test',
            'password' => 'TeacherPass123!',
            'role' => 'teacher',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $teacherUser->syncRoles(['Teacher']);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-SYNC-02',
            'user_id' => $teacherUser->id,
            'teacher_id' => $teacher->id,
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branchA->id,
            'job_title' => 'Teacher',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'base_salary' => 90000.00,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.teachers.update', $teacher), [
                'name' => 'Teacher Two Updated',
                'email' => 'teacher2.sync@school.test',
                'department_id' => $this->deptB->id,
                'branch_id' => $this->branchB->id,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertFalse($teacherUser->fresh()->is_active);
        $this->assertSame($this->branchB->id, $teacherUser->fresh()->branch_id);
        $this->assertSame($this->deptB->id, $employee->fresh()->department_id);
        $this->assertSame($this->branchB->id, $employee->fresh()->branch_id);
        $this->assertSame('terminated', $employee->fresh()->employment_status);
    }

    #[Test]
    public function updating_employee_syncs_linked_user_and_teacher(): void
    {
        $empUser = User::create([
            'name' => 'Accountant Staff',
            'email' => 'acc.sync@school.test',
            'password' => 'AccountPass123!',
            'role' => 'accountant',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $empUser->syncRoles(['Accountant']);

        $employee = Employee::create([
            'employee_no' => 'EMP-SYNC-03',
            'user_id' => $empUser->id,
            'teacher_id' => null,
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branchA->id,
            'job_title' => 'Accountant',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'base_salary' => 85000.00,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.hr.employees.update', $employee), [
                'employee_no' => 'EMP-SYNC-03',
                'user_id' => $empUser->id,
                'teacher_id' => null,
                'department_id' => $this->deptB->id,
                'branch_id' => $this->branchB->id,
                'job_title' => 'Senior Accountant',
                'hire_date' => '2024-01-01',
                'employment_status' => 'terminated',
                'base_salary' => 95000.00,
                'apit_mode' => 'primary',
            ])
            ->assertRedirect(route('admin.hr.employees.index'));

        $this->assertSame($this->branchB->id, $empUser->fresh()->branch_id);
        $this->assertFalse($empUser->fresh()->is_active);
    }
}
