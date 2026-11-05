<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    public function test_existing_unlinked_student_account_can_be_linked_and_use_the_portal(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'A');
        $studentUser = User::create([
            'name' => 'Existing Student',
            'email' => 'existing-student@school.test',
            'password' => 'password',
            'role' => 'Student',
        ]);
        $studentUser->syncRoles(['Student']);

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'existing_user_id' => $studentUser->id,
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ])->assertRedirect(route('admin.students.index'));

        $student = Student::where('user_id', $studentUser->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^2026-CS-\d{4}$/', $student->admission_no);
        $this->assertDatabaseHas('students', [
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
        ]);
        $this->assertSame('student', $studentUser->fresh()->role);

        $this->actingAs($studentUser->fresh())
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_new_student_enrollment_assigns_a_linked_profile_and_portal_role(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 11', 'B');

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'New Student',
            'email' => 'new-student@school.test',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ])->assertRedirect(route('admin.students.index'));

        $student = Student::where('user_id', User::where('email', 'new-student@school.test')->value('id'))->firstOrFail();
        $this->assertSame($student->user_id, $student->user->id);
        $this->assertTrue($student->user->hasRole('Student'));
        $this->assertTrue($student->user->must_change_password);

        $this->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_admin_can_select_an_existing_unlinked_student_login_on_the_enrollment_form(): void
    {
        $this->seedRolesAndAdmin();
        $studentUser = User::create([
            'name' => 'Student Awaiting Profile',
            'email' => 'awaiting-profile@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $studentUser->syncRoles(['Student']);

        $this->actingAs($this->admin)
            ->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('Existing student login')
            ->assertSee('awaiting-profile@school.test');
    }

    public function test_generic_user_management_cannot_create_unlinked_student_accounts(): void
    {
        $this->seedRolesAndAdmin();

        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertDontSee('<option value="Student">Student</option>', false);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Unlinked Student',
            'email' => 'unlinked-student@school.test',
            'password' => 'Password123!',
            'role' => 'Student',
        ])->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'unlinked-student@school.test']);
    }

    private function seedRolesAndAdmin(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $this->admin = User::create([
            'name' => 'Institution Admin',
            'email' => 'institution-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $this->admin->syncRoles(['Institution Admin']);
    }

    /**
     * @return array{SchoolClass, Guardian}
     */
    private function createEnrollmentContext(string $name, string $section): array
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'CS']);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);
        $class = SchoolClass::create([
            'name' => $name,
            'section' => $section,
            'capacity' => 40,
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $guardianUser = User::create([
            'name' => 'Student Guardian',
            'email' => 'guardian-'.$section.'@school.test',
            'password' => 'password',
            'role' => 'parent',
        ]);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '123456789']);

        return [$class, $guardian];
    }
}
