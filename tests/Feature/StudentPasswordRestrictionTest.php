<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentPasswordRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $studentUser;
    private Student $student;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->admin = User::factory()->create([
            'email' => 'admin-pwd-test@school.test',
            'role' => 'admin',
            'must_change_password' => false,
        ]);
        $this->admin->syncRoles(['Institution Admin']);

        $branch = Branch::create([
            'name' => 'Colombo Campus',
            'code' => 'CMB',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
            'is_main' => true,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $department = Department::create([
            'name' => 'High School',
            'code' => 'HS-PWD',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'National Curriculum',
            'code' => 'NC-PWD',
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->class = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'branch_id' => $branch->id,
            'academic_year_id' => $academicYear->id,
            'program_id' => $program->id,
            'capacity' => 30,
        ]);

        $this->studentUser = User::factory()->create([
            'name' => 'Rohan Ali',
            'email' => 'rohan.pwd.test@school.test',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'student',
            'must_change_password' => false,
            'branch_id' => $branch->id,
        ]);
        $this->studentUser->syncRoles(['Student']);

        $guardianUser = User::factory()->create([
            'role' => 'parent',
        ]);
        $guardian = \App\Models\Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '+94771234999',
            'relationship' => 'Parent',
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $this->class->id,
            'branch_id' => $branch->id,
            'academic_year_id' => $academicYear->id,
            'program_id' => $program->id,
            'admission_no' => '2026-NCPW-0001',
            'guardian_id' => $guardian->id,
            'status' => 'active',
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '200412345678',
        ]);
    }

    public function test_student_profile_edit_does_not_contain_password_fields(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.profile.edit'));

        $response->assertOk();
        $response->assertDontSee('name="password"', false);
        $response->assertDontSee('name="password_confirmation"', false);
        $response->assertSee('Student passwords can only be changed or reset by the School Administrator');
    }

    public function test_student_cannot_submit_password_update_to_profile(): void
    {
        $originalHash = $this->studentUser->password;

        $response = $this->actingAs($this->studentUser)
            ->put(route('student.profile.update'), [
                'name' => 'Rohan Ali',
                'password' => 'HackedPassword123!',
                'password_confirmation' => 'HackedPassword123!',
            ]);

        $response->assertSessionHasErrors(['password']);
        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->studentUser->password));
        $this->assertSame($originalHash, $this->studentUser->password);
    }

    public function test_student_can_update_name_without_password(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->put(route('student.profile.update'), [
                'name' => 'Rohan Tariq Ali',
            ]);

        $response->assertRedirect(route('student.profile'));
        $this->studentUser->refresh();
        $this->assertSame('Rohan Tariq Ali', $this->studentUser->name);
    }

    public function test_admin_can_update_student_password_and_student_logs_in_without_forced_change(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.students.update', $this->student), [
                'name' => 'Rohan Ali Updated',
                'email' => 'rohan.pwd.test@school.test',
                'password' => 'AdminAssignedPass123!',
                'class_id' => $this->class->id,
                'admission_no' => '2026-NCPW-0001',
                'guardian_id' => $this->student->guardian_id,
                'status' => 'active',
                'document_type' => 'national_id',
                'issuing_country' => 'LK',
                'document_number' => '200412345678',
            ]);

        $response->assertRedirect(route('admin.students.index'));

        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('AdminAssignedPass123!', $this->studentUser->password));
        $this->assertFalse((bool) $this->studentUser->must_change_password);

        // Student can log in and view dashboard directly without being redirected to password.change
        $this->actingAs($this->studentUser)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewIs('Student.StudentDashboard');
    }

    public function test_student_cannot_access_self_service_password_change_route(): void
    {
        $this->assertFalse((bool) $this->studentUser->must_change_password);

        $response = $this->actingAs($this->studentUser)
            ->get(route('password.change'));

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');

        $updateResponse = $this->actingAs($this->studentUser)
            ->post(route('password.change.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $updateResponse->assertRedirect(route('student.dashboard'));
        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->studentUser->password));
    }

    public function test_student_cannot_reset_password_via_forgot_password_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => $this->studentUser->email,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->studentUser->password));
    }
}
