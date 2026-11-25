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

class StaffPersonalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;
    private Employee $teacherEmployee;
    private User $accountantUser;
    private Employee $accountantEmployee;
    private Branch $branch;
    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $this->branch = Branch::create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->dept = Department::create([
            'name' => 'Science',
            'code' => 'SCI',
            'is_active' => true,
        ]);

        // Create teacher
        $this->teacherUser = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah.teacher@school.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->teacherUser->syncRoles(['Teacher']);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'department_id' => $this->dept->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->teacherEmployee = Employee::create([
            'employee_no' => 'EMP-T-001',
            'user_id' => $this->teacherUser->id,
            'teacher_id' => $this->teacher->id,
            'department_id' => $this->dept->id,
            'branch_id' => $this->branch->id,
            'job_title' => 'Senior Teacher',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'base_salary' => 95000.00,
        ]);

        // Create accountant (non-teacher employee)
        $this->accountantUser = User::create([
            'name' => 'John Accountant',
            'email' => 'john.accountant@school.test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->accountantUser->syncRoles(['Accountant']);

        $this->accountantEmployee = Employee::create([
            'employee_no' => 'EMP-A-001',
            'user_id' => $this->accountantUser->id,
            'teacher_id' => null,
            'department_id' => $this->dept->id,
            'branch_id' => $this->branch->id,
            'job_title' => 'Accountant',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'base_salary' => 80000.00,
        ]);
    }

    #[Test]
    public function teacher_can_view_own_attendance_details_in_teacher_profile(): void
    {
        // Seed attendance records for teacher
        StaffAttendance::create([
            'teacher_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => $this->teacherUser->name,
            'attendance_date' => today()->toDateString(),
            'check_in' => '08:00',
            'check_out' => '16:30',
            'notes' => 'On-time shift',
        ]);

        StaffAttendance::create([
            'teacher_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => $this->teacherUser->name,
            'attendance_date' => today()->subDay()->toDateString(),
            'check_in' => '08:45',
            'check_out' => '17:00',
            'notes' => 'Traffic delay - Late',
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.profile'));

        $response->assertOk()
            ->assertSee('My Personal Attendance History')
            ->assertSee('Sarah Connor')
            ->assertSee('EMP-T-001')
            ->assertSee('08:00 AM')
            ->assertSee('04:30 PM')
            ->assertSee('08:45 AM')
            ->assertSee('05:00 PM')
            ->assertSee('8h 30m')
            ->assertSee('8h 15m')
            ->assertSee('Traffic delay - Late')
            ->assertSee('Days Present');
    }

    #[Test]
    public function employee_can_view_own_attendance_details_via_my_attendance(): void
    {
        // Seed attendance records for non-teacher employee
        StaffAttendance::create([
            'teacher_id' => null,
            'employee_id' => $this->accountantEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => $this->accountantUser->name,
            'attendance_date' => today()->toDateString(),
            'check_in' => '08:15',
            'check_out' => '16:45',
            'notes' => 'Finance shift completed',
        ]);

        $response = $this->actingAs($this->accountantUser)
            ->get(route('my-attendance'));

        $response->assertOk()
            ->assertSee('My Personal Attendance History')
            ->assertSee('John Accountant')
            ->assertSee('EMP-A-001')
            ->assertSee('08:15 AM')
            ->assertSee('04:45 PM')
            ->assertSee('8h 30m')
            ->assertSee('Finance shift completed');
    }

    #[Test]
    public function staff_attendance_model_computes_correct_status_and_hours(): void
    {
        $onTime = StaffAttendance::create([
            'teacher_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => 'Test Staff',
            'attendance_date' => today()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => '16:00:00',
            'notes' => 'Good morning',
        ]);

        $this->assertSame('present', $onTime->status);
        $this->assertSame('08:00 AM', $onTime->check_in_formatted);
        $this->assertSame('04:00 PM', $onTime->check_out_formatted);
        $this->assertSame(8.0, $onTime->working_hours);
        $this->assertSame('8h 0m', $onTime->working_hours_formatted);

        $late = StaffAttendance::create([
            'teacher_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => 'Late Staff',
            'attendance_date' => today()->subDay()->toDateString(),
            'check_in' => '08:45:00',
            'check_out' => '17:00:00',
            'notes' => 'Late arrival due to rain',
        ]);

        $this->assertSame('late', $late->status);
        $this->assertSame(8.25, $late->working_hours);
        $this->assertSame('8h 15m', $late->working_hours_formatted);

        $inProgress = StaffAttendance::create([
            'teacher_id' => $this->teacher->id,
            'employee_id' => $this->teacherEmployee->id,
            'branch_id' => $this->branch->id,
            'staff_name' => 'Active Staff',
            'attendance_date' => today()->subDays(2)->toDateString(),
            'check_in' => '08:10:00',
            'check_out' => null,
            'notes' => null,
        ]);

        $this->assertSame('Active / In Progress', $inProgress->check_out_formatted);
        $this->assertSame('In progress', $inProgress->working_hours_formatted);
    }
}
