<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\BranchTransferService;
use App\Services\EnrollmentService;
use App\Services\HrPayrollService;
use App\Support\AdmissionNumberGenerator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InternationalMultiBranchAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $mainBranch;
    private Branch $kandyBranch;
    private AcademicYear $year;
    private Program $program;
    private SchoolClass $mainClass;
    private SchoolClass $kandyClass;
    private \App\Models\Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $this->admin = User::updateOrCreate(
            ['email' => 'admin@school.test'],
            [
                'name' => 'System Administrator',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->syncRoles(['Super Admin', 'Institution Admin']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $gUser = User::create([
            'name' => 'Primary Guardian',
            'email' => 'guardian@test.local',
            'password' => 'password123',
            'role' => 'parent',
        ]);
        $this->guardian = \App\Models\Guardian::create([
            'user_id' => $gUser->id,
            'phone' => '+94771234567',
        ]);

        $this->mainBranch = Branch::create([
            'code' => 'MAIN',
            'name' => 'IGK Main Campus',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->kandyBranch = Branch::create([
            'code' => 'KAN',
            'name' => 'IGK Kandy Campus',
            'is_main' => false,
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $dept = Department::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $this->program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Computer Science',
            'code' => 'CS',
            'is_active' => true,
        ]);

        $this->mainClass = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'capacity' => 40,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'branch_id' => $this->mainBranch->id,
        ]);

        $this->kandyClass = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'K',
            'capacity' => 40,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'branch_id' => $this->kandyBranch->id,
        ]);
    }

    public function test_student_registration_and_branch_assignment(): void
    {
        $this->actingAs($this->admin);

        $student = EnrollmentService::enrollStudent([
            'name' => 'John Doe',
            'email' => 'john.doe@test.local',
            'password' => 'SecurePass123!',
            'class_id' => $this->mainClass->id,
            'branch_id' => $this->mainBranch->id,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '200123456789',
            'guardian_ids' => [$this->guardian->id],
        ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->assertEquals($this->mainBranch->id, $student->user->branch_id);
    }

    public function test_safe_admission_number_generation_continues_sequence(): void
    {
        $this->actingAs($this->admin);

        // Pre-create a student in Kandy with admission ADM-KAN-025
        $user = User::create([
            'name' => 'Existing Kandy Student',
            'email' => 'kandy.prev@test.local',
            'password' => 'password',
            'role' => 'student',
            'branch_id' => $this->kandyBranch->id,
        ]);
        Student::create([
            'user_id' => $user->id,
            'class_id' => $this->kandyClass->id,
            'branch_id' => $this->kandyBranch->id,
            'admission_no' => 'ADM-KAN-025',
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '199912345678',
        ]);

        // Next number for Kandy should be ADM-KAN-026, never restarting to 001
        $next = AdmissionNumberGenerator::nextForBranch($this->kandyBranch->id);
        $this->assertEquals('ADM-KAN-026', $next);
    }

    public function test_student_transfer_between_branches_with_history(): void
    {
        $this->actingAs($this->admin);

        // Enroll a student in Main Branch
        $student = EnrollmentService::enrollStudent([
            'name' => 'Alice Transfer',
            'email' => 'alice.transfer@test.local',
            'password' => 'SecurePass123!',
            'class_id' => $this->mainClass->id,
            'branch_id' => $this->mainBranch->id,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '200212345678',
            'guardian_ids' => [$this->guardian->id],
        ]);

        $oldAdmissionNo = $student->admission_no;

        // Perform transfer to Kandy Branch
        $transferred = BranchTransferService::transferStudent(
            $student,
            $this->kandyBranch->id,
            $this->kandyClass->id,
            '2026-11-14',
            'Family relocated to Kandy',
            $this->admin->id
        );

        $this->assertEquals($this->kandyBranch->id, $transferred->branch_id);
        $this->assertEquals($this->kandyClass->id, $transferred->class_id);
        $this->assertNotEquals($oldAdmissionNo, $transferred->admission_no);
        $this->assertStringContainsString('KAN', $transferred->admission_no);

        // Verify history was recorded in branch_transfers table
        $this->assertDatabaseHas('branch_transfers', [
            'person_id' => $student->id,
            'person_category' => 'student',
            'from_branch_id' => $this->mainBranch->id,
            'to_branch_id' => $this->kandyBranch->id,
            'previous_admission_no' => $oldAdmissionNo,
            'new_admission_no' => $transferred->admission_no,
            'transferred_by' => $this->admin->id,
            'reason' => 'Family relocated to Kandy',
        ]);

        // Verify user account branch is synchronised
        $this->assertEquals($this->kandyBranch->id, $transferred->user->fresh()->branch_id);
    }

    public function test_employee_transfer_between_branches(): void
    {
        $this->actingAs($this->admin);

        $empUser = User::create([
            'name' => 'Jane Teacher',
            'email' => 'jane.teacher@test.local',
            'password' => 'Password123!',
            'role' => 'teacher',
            'branch_id' => $this->mainBranch->id,
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-MAIN-001',
            'user_id' => $empUser->id,
            'job_title' => 'Senior Physics Teacher',
            'hire_date' => '2026-01-10',
            'employment_status' => 'active',
            'base_salary' => '60000.00',
            'apit_mode' => 'primary',
            'branch_id' => $this->mainBranch->id,
        ]);

        $transferred = BranchTransferService::transferEmployee(
            $employee,
            $this->kandyBranch->id,
            '2026-11-14',
            'Faculty rotation to Kandy Campus',
            $this->admin->id
        );

        $this->assertEquals($this->kandyBranch->id, $transferred->branch_id);
        $this->assertEquals($this->kandyBranch->id, $empUser->fresh()->branch_id);

        $this->assertDatabaseHas('branch_transfers', [
            'person_id' => $employee->id,
            'person_category' => 'employee',
            'from_branch_id' => $this->mainBranch->id,
            'to_branch_id' => $this->kandyBranch->id,
            'reason' => 'Faculty rotation to Kandy Campus',
        ]);
    }

    public function test_leave_based_automatic_salary_deduction_in_payroll(): void
    {
        $this->actingAs($this->admin);

        $empUser = User::create([
            'name' => 'Robert Staff',
            'email' => 'robert.staff@test.local',
            'password' => 'Password123!',
            'role' => 'staff',
            'branch_id' => $this->mainBranch->id,
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-STAFF-001',
            'user_id' => $empUser->id,
            'job_title' => 'Lab Technician',
            'hire_date' => '2026-01-10',
            'employment_status' => 'active',
            'base_salary' => '60000.00',
            'apit_mode' => 'exempt',
            'branch_id' => $this->mainBranch->id,
        ]);

        // Create approved unpaid leave of 2 days in November 2026
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'unpaid',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'days' => 2,
            'status' => 'approved',
            'reason' => 'Personal emergency',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);

        // Also create approved paid annual leave (should NOT be deducted)
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'days' => 2,
            'status' => 'approved',
            'reason' => 'Vacation',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);

        // Generate payroll for November 2026
        $run = HrPayrollService::generatePayroll('2026-11-01', '2026-11-30', $this->admin->id);

        $entry = PayrollEntry::where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->first();
        $this->assertNotNull($entry);

        // Monthly salary: 60,000. 30 working days -> 2,000 / day.
        // 2 unpaid days -> 4,000 deduction.
        $this->assertEquals(2, $entry->unpaid_leave_days);
        $this->assertEquals('4000.00', $entry->leave_deduction);

        // Verify deduction record is created
        $this->assertDatabaseHas('payroll_deductions', [
            'payroll_entry_id' => $entry->id,
            'employee_id' => $employee->id,
            'type' => 'leave_unpaid',
            'amount' => 4000.00,
            'days' => 2,
        ]);
    }

    public function test_manual_salary_deduction_and_payslip_view(): void
    {
        $this->actingAs($this->admin);

        $empUser = User::create([
            'name' => 'Sarah Admin',
            'email' => 'sarah.admin@test.local',
            'password' => 'Password123!',
            'role' => 'staff',
            'branch_id' => $this->mainBranch->id,
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-SARAH-001',
            'user_id' => $empUser->id,
            'job_title' => 'Clerk',
            'hire_date' => '2026-01-10',
            'employment_status' => 'active',
            'base_salary' => '50000.00',
            'apit_mode' => 'exempt',
            'branch_id' => $this->mainBranch->id,
        ]);

        $run = HrPayrollService::generatePayroll('2026-10-01', '2026-10-31', $this->admin->id);
        $entry = PayrollEntry::where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->first();

        // Update with manual deduction
        HrPayrollService::updateEntry(
            $entry,
            '0.00',
            '1500.00',
            'Salary advance repaid',
            '0.00',
            '1500.00',
            'Salary advance repaid',
            $this->admin->id
        );

        $entry->refresh();
        $this->assertEquals('1500.00', $entry->manual_deduction);
        $this->assertEquals('Salary advance repaid', $entry->manual_deduction_reason);

        // Verify payslip route renders successfully
        $response = $this->get(route('admin.hr.payroll.entries.payslip', $entry));
        $response->assertStatus(200);
        $response->assertSee('PAYSLIP');
        $response->assertSee('Salary advance repaid');
    }

    public function test_student_detail_view_and_curriculum_audit_tabs(): void
    {
        $this->actingAs($this->admin);

        $student = EnrollmentService::enrollStudent([
            'name' => 'David Detail',
            'email' => 'david.detail@test.local',
            'password' => 'SecurePass123!',
            'class_id' => $this->mainClass->id,
            'branch_id' => $this->mainBranch->id,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '200312345678',
            'guardian_ids' => [$this->guardian->id],
        ]);

        // Student detail profile renders
        $response = $this->get(route('admin.students.show', $student));
        $response->assertStatus(200);
        $response->assertSee('David Detail');
        $response->assertSee($student->admission_no);
        $response->assertSee('Personal & Guardian', false);
        $response->assertSee('Campus Transfer History');

        // Curriculum index renders without raw source code
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY']);
        $this->post(route('admin.curricula.store'), [
            'program_id' => $this->program->id,
            'subject_id' => $subject->id,
            'term_number' => 1,
            'is_elective' => false,
        ]);

        $curricResponse = $this->get(route('admin.curricula.index'));
        $curricResponse->assertStatus(200);
        $curricResponse->assertSee('Curriculum Builder');
        $curricResponse->assertSee('Curriculum Audit Trail');
        // Must NOT render escaped HTML code string
        $curricResponse->assertDontSee('&lt;span class="badge bg-success"&gt;Core&lt;/span&gt;', false);
    }

    public function test_enrollment_register_report_renders_interactive_modal_and_links(): void
    {
        $this->actingAs($this->admin);

        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MTH-10']);
        $tUser = User::create(['name' => 'Teacher One', 'email' => 't1@test.local', 'password' => 'password123', 'role' => 'teacher']);
        $teacher = \App\Models\Teacher::create(['user_id' => $tUser->id, 'phone' => '111222333']);

        $classSubject = \App\Models\ClassSubject::create([
            'class_id' => $this->mainClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = EnrollmentService::enrollStudent([
            'name' => 'Report Enrolled Student',
            'email' => 'report.enrolled@test.local',
            'password' => 'SecurePass123!',
            'class_id' => $this->mainClass->id,
            'branch_id' => $this->mainBranch->id,
            'program_id' => $this->program->id,
            'academic_year_id' => $this->year->id,
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '200512345678',
            'guardian_ids' => [$this->guardian->id],
        ]);

        $response = $this->get(route('reports.enrollment', [
            'class_id' => $this->mainClass->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Enrollment Register');
        $response->assertSee('Report Enrolled Student');
        $response->assertSee(route('admin.students.show', $student));
        $response->assertSee('View Profile');
        $response->assertSee('enrollModal' . $classSubject->id);
    }

    public function test_teacher_branch_assignment_and_transfer_workflow(): void
    {
        $this->actingAs($this->admin);

        // 1. Create Teacher with branch assignment
        $response = $this->post(route('admin.teachers.store'), [
            'name' => 'Professor Perera',
            'email' => 'perera.teacher@test.local',
            'password' => 'SecurePass123!',
            'branch_id' => $this->mainBranch->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'perera.teacher@test.local',
            'branch_id' => $this->mainBranch->id,
        ]);

        $teacher = \App\Models\Teacher::whereHas('user', fn ($q) => $q->where('email', 'perera.teacher@test.local'))->firstOrFail();
        $this->assertEquals($this->mainBranch->id, $teacher->branch_id);
        $this->assertEquals($this->mainBranch->id, $teacher->user->branch_id);

        // 2. Transfer Teacher to Kandy Branch
        $transferResponse = $this->post(route('admin.teachers.transfer', $teacher), [
            'to_branch_id' => $this->kandyBranch->id,
            'transfer_date' => '2026-11-14',
            'reason' => 'Transferred to lead Kandy faculty team',
        ]);

        $transferResponse->assertSessionHas('success', 'Teacher transferred to new campus successfully.');

        $this->assertDatabaseHas('branch_transfers', [
            'person_id' => $teacher->id,
            'person_category' => 'teacher',
            'from_branch_id' => $this->mainBranch->id,
            'to_branch_id' => $this->kandyBranch->id,
            'reason' => 'Transferred to lead Kandy faculty team',
        ]);

        $this->assertEquals($this->kandyBranch->id, $teacher->fresh()->branch_id);
        $this->assertEquals($this->kandyBranch->id, $teacher->user->fresh()->branch_id);

        // 3. Index page displays branch and transfer badges
        $indexResponse = $this->get(route('admin.teachers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Professor Perera');
        $indexResponse->assertSee('IGK Kandy Campus');
        $indexResponse->assertSee('Transferred');
    }

    public function test_super_admin_dedicated_salary_deductions_and_teacher_profile_transparency(): void
    {
        // 1. Create Teacher, User, and Employee
        $teacherUser = User::create([
            'name' => 'Nimal Silva',
            'email' => 'nimal.silva@test.local',
            'password' => 'SecurePass123!',
            'role' => 'teacher',
            'branch_id' => $this->mainBranch->id,
            'is_active' => true,
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'branch_id' => $this->mainBranch->id,
            'status' => 'active',
            'phone' => '+94711112222',
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP-0099',
            'user_id' => $teacherUser->id,
            'teacher_id' => $teacher->id,
            'job_title' => 'Senior Mathematics Teacher',
            'hire_date' => '2025-01-01',
            'employment_status' => 'active',
            'base_salary' => '100000.00',
            'apit_mode' => 'primary',
            'branch_id' => $this->mainBranch->id,
        ]);

        // 2. Non-Super Admin cannot access salary deductions module
        $this->actingAs($teacherUser);
        $forbiddenResponse = $this->get(route('admin.salary-deductions.index'));
        $forbiddenResponse->assertForbidden();

        // 3. Super Admin accesses and configures automatic attendance deduction policy
        $this->actingAs($this->admin);
        $superAdminIndex = $this->get(route('admin.salary-deductions.index'));
        $superAdminIndex->assertOk();
        $superAdminIndex->assertSee('Salary Deductions Console');
        $superAdminIndex->assertSee('Super Admin Exclusive');

        // Configure Super Admin fixed amount deduction policy for attendance
        $policyResponse = $this->post(route('admin.salary-deductions.policy.update'), [
            'auto_deduction_enabled' => 1,
            'auto_deduction_mode' => 'fixed_amount',
            'auto_deduction_fixed_amount' => '3000.00',
            'auto_deduction_late_penalty' => '500.00',
        ]);
        $policyResponse->assertRedirect(route('admin.salary-deductions.index'));
        $this->assertEquals('fixed_amount', \App\Models\Setting::get('payroll.auto_deduction_mode'));
        $this->assertEquals(3000.00, (float) \App\Models\Setting::get('payroll.auto_deduction_fixed_amount'));

        // Log 2 days of approved unpaid leave to test automatic attendance deduction
        \App\Models\LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'unpaid',
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-06',
            'days' => 2,
            'reason' => 'Family emergency absence',
            'status' => 'approved',
        ]);

        // 4. Super Admin records a manual deduction (wished / custom amount)
        $deductionResponse = $this->post(route('admin.salary-deductions.store'), [
            'employee_id' => $employee->id,
            'type' => 'disciplinary',
            'amount' => '7500.00',
            'payroll_month' => '2026-11',
            'reason' => 'Administrative penalty for unexcused absence on examination day',
            'notes' => 'Authorized by Head of Institution and verified by Super Admin',
        ]);
        $deductionResponse->assertRedirect();

        $this->assertDatabaseHas('payroll_deductions', [
            'employee_id' => $employee->id,
            'type' => 'disciplinary',
            'amount' => 7500.00,
            'payroll_month' => '2026-11',
            'status' => 'pending',
        ]);

        // 5. Payroll is generated and calculates automatic attendance deduction + applies manual deduction
        $payrollRun = HrPayrollService::generatePayroll('2026-11-01', '2026-11-30', (int) $this->admin->id);
        $payrollEntry = PayrollEntry::where('employee_id', $employee->id)->where('payroll_run_id', $payrollRun->id)->firstOrFail();

        // 2 unpaid days * 3000.00 fixed daily rate = 6000.00 automatic deduction
        $this->assertEquals('6000.00', (string) $payrollEntry->leave_deduction);
        $this->assertEquals('7500.00', (string) $payrollEntry->manual_deduction);
        $this->assertEquals('13500.00', (string) $payrollEntry->deduction);
        $this->assertStringContainsString('Administrative penalty', (string) $payrollEntry->manual_deduction_reason);

        // Verify status transitioned from pending to applied
        $this->assertDatabaseHas('payroll_deductions', [
            'employee_id' => $employee->id,
            'payroll_entry_id' => $payrollEntry->id,
            'status' => 'applied',
        ]);

        // 6. Teacher logs into their OWN page and verifies full details, salary, and both deduction types
        $this->actingAs($teacherUser);
        $profileResponse = $this->get(route('teacher.profile'));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Nimal Silva');
        $profileResponse->assertSee('EMP-0099');
        $profileResponse->assertSee('IGK Main Campus');
        $profileResponse->assertSee('Rs. 100,000.00'); // Base salary
        $profileResponse->assertSee('-Rs. 7,500.00'); // Manual Super admin deduction
        $profileResponse->assertSee('-Rs. 6,000.00'); // Automatic attendance deduction
        $profileResponse->assertSee('Administrative penalty for unexcused absence on examination day');
        $profileResponse->assertSee('Applied to Payroll');

        // 7. Teacher can view their own official payslip with night-mode immune styling
        $payslipResponse = $this->get(route('teacher.payslip', $payrollEntry));
        $payslipResponse->assertOk();
        $payslipResponse->assertSee('PAYSLIP');
        $payslipResponse->assertSee('Nimal Silva');
        $payslipResponse->assertSee('EMP-0099');
        $payslipResponse->assertSee('Automatic Attendance Deduction');
        $payslipResponse->assertSee('-Rs. 6,000.00');
        $payslipResponse->assertSee('-Rs. 7,500.00');

        // 7. Another teacher cannot view this teacher's payslip
        $otherUser = User::create([
            'name' => 'Other Teacher',
            'email' => 'other.teacher@test.local',
            'password' => 'password123',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $otherUser->assignRole('Teacher');

        $this->actingAs($otherUser);
        $unauthorizedPayslip = $this->get(route('teacher.payslip', $payrollEntry));
        $unauthorizedPayslip->assertForbidden();
    }
}
