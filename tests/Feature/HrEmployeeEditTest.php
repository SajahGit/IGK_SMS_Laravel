<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrEmployeeEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_edit_form_keeps_its_linked_user_and_teacher_available(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $branch = Branch::create([
            'code' => 'INTL',
            'name' => 'International Campus',
            'is_main' => true,
            'is_active' => true,
        ]);
        $admin = User::create([
            'name' => 'International School Super Admin',
            'email' => 'hr-edit-admin@school.test',
            'password' => 'password',
            'role' => 'super-admin',
            'must_change_password' => false,
        ]);
        $admin->syncRoles(['Super Admin']);

        $linkedUser = User::create([
            'name' => 'Aisha Rahman',
            'email' => 'aisha.rahman@school.test',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $teacher = Teacher::create(['user_id' => $linkedUser->id]);
        $employee = Employee::create([
            'employee_no' => 'INTL-EMP-001',
            'user_id' => $linkedUser->id,
            'teacher_id' => $teacher->id,
            'job_title' => 'IB Mathematics Teacher',
            'hire_date' => '2025-08-01',
            'employment_status' => 'active',
            'base_salary' => '85000.00',
            'apit_mode' => 'primary',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.hr.employees.edit', $employee))
            ->assertOk()
            ->assertSee('aisha.rahman@school.test')
            ->assertSee('INTL-EMP-001');
    }

    public function test_employee_number_is_normalized_and_invalid_job_titles_or_negative_salary_are_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create([
            'code' => 'INTL',
            'name' => 'International Campus',
            'is_main' => true,
            'is_active' => true,
        ]);
        $admin = User::create([
            'name' => 'International School Super Admin',
            'email' => 'hr-validation-admin@school.test',
            'password' => 'password',
            'role' => 'super-admin',
            'must_change_password' => false,
        ]);
        $admin->syncRoles(['Super Admin']);

        $this->actingAs($admin)
            ->post(route('admin.hr.employees.store'), [
                'employee_no' => ' emp -001 ',
                'job_title' => 'Teacher',
                'hire_date' => '2025-08-01',
                'employment_status' => 'active',
                'base_salary' => '0',
                'apit_mode' => 'primary',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('admin.hr.employees.index'));

        $this->assertDatabaseHas('employees', ['employee_no' => 'EMP-001']);

        $this->from(route('admin.hr.employees.create'))
            ->post(route('admin.hr.employees.store'), [
                'employee_no' => 'EMP-002',
                'job_title' => 'Teacher 2',
                'hire_date' => '2025-08-01',
                'employment_status' => 'active',
                'base_salary' => '-1',
                'apit_mode' => 'primary',
                'branch_id' => $branch->id,
            ])
            ->assertSessionHasErrors(['job_title', 'base_salary']);
    }
}
