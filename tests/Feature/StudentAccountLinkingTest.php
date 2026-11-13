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
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'LINK123',
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
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'NEW1234',
            'nationality' => 'Indian',
        ])->assertRedirect(route('admin.students.index'));

        $student = Student::where('user_id', User::where('email', 'new-student@school.test')->value('id'))->firstOrFail();
        $this->assertSame($student->user_id, $student->user->id);
        $this->assertSame('Indian', $student->nationality);
        $this->assertTrue($student->user->hasRole('Student'));
        $this->assertTrue($student->user->must_change_password);

        $this->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_student_enrollment_reports_invalid_name_and_country_phone_on_their_fields(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'C');

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aisha2 Hassan',
            'email' => 'aisha.hassan@school.test',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'phone' => '077 12',
            'phone_region' => 'LK',
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'BAD1234',
        ])->assertSessionHasErrors(['name', 'phone']);

        $this->assertDatabaseMissing('users', ['email' => 'aisha.hassan@school.test']);
    }

    public function test_student_enrollment_requires_document_type_issuing_country_and_document_number(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'D');

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aisha Hassan',
            'email' => 'aisha.document@school.test',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ])->assertSessionHasErrors(['document_type', 'issuing_country', 'document_number']);

        $this->assertDatabaseMissing('users', ['email' => 'aisha.document@school.test']);
    }

    public function test_student_nationality_must_be_selected_from_the_supported_country_list(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 7', 'E');

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aisha Rahman',
            'email' => 'aisha.nationality@school.test',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'NAT12345',
            'nationality' => 'Sri Lankan123',
        ])->assertSessionHasErrors('nationality');

        $this->assertDatabaseMissing('users', ['email' => 'aisha.nationality@school.test']);
    }

    public function test_guardian_nationality_must_be_selected_from_the_supported_country_list(): void
    {
        $this->seedRolesAndAdmin();

        $this->actingAs($this->admin)->post(route('admin.guardians.store'), [
            'name' => 'Aisha Rahman',
            'email' => 'aisha.guardian-nationality@school.test',
            'password' => 'Password123!',
            'phone' => '0771234568',
            'phone_region' => 'LK',
            'nationality' => 'Sri Lankan123',
        ])->assertSessionHasErrors('nationality');

        $this->assertDatabaseMissing('users', ['email' => 'aisha.guardian-nationality@school.test']);
    }

    public function test_full_classes_are_labelled_and_rejected_during_student_enrollment(): void
    {
        $this->seedRolesAndAdmin();
        [$class, $guardian] = $this->createEnrollmentContext('Grade 7', 'F');
        $class->update(['capacity' => 1]);
        $existingUser = User::create([
            'name' => 'Amara Silva',
            'email' => 'amara.existing@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        Student::create([
            'user_id' => $existingUser->id,
            'class_id' => $class->id,
            'admission_no' => '2026-G7-0001',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('1/1 enrolled')
            ->assertSee('— FULL')
            ->assertSee('disabled', false);

        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Aisha Rahman',
            'email' => 'aisha.full-class@school.test',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'FULL1234',
        ])->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('users', ['email' => 'aisha.full-class@school.test']);
    }

    public function test_admin_can_select_an_existing_unlinked_student_login_on_the_enrollment_form(): void
    {
        $this->seedRolesAndAdmin();
        $guardianUser = User::create([
            'name' => 'Aisha Rahman',
            'email' => 'aisha.guardian@school.test',
            'password' => 'password',
            'role' => 'parent',
        ]);
        Guardian::create(['user_id' => $guardianUser->id, 'phone' => '+94771234567']);
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
            ->assertSee('awaiting-profile@school.test')
            ->assertSee('data-nationality-picker', false)
            ->assertSee('Search nationality')
            ->assertSee('data-document-number', false)
            ->assertSee('data-searchable-select', false)
            ->assertSee('data-country-search', false)
            ->assertSee('maxlength="20" autocomplete="off" disabled required', false)
            ->assertSee('name="document_number"', false)
            ->assertSee('name="guardian_ids[]"', false)
            ->assertSee('data-guardian-selector', false)
            ->assertSee('data-guardian-search', false)
            ->assertSee('fi fi-lk', false)
            ->assertSee('fi fi-ac', false)
            ->assertSee('Ascension Island')
            ->assertSee('fi fi-ta', false)
            ->assertSee('+94771234567');

        $this->actingAs($this->admin)
            ->get(route('admin.guardians.create'))
            ->assertOk()
            ->assertSee('data-international-phone', false)
            ->assertSee('fi fi-lk', false);

        $this->assertFileExists(public_path('vendor/flag-icons/flags/4x3/lk.svg'));
        $this->assertFileExists(public_path('vendor/flag-icons/flags/4x3/ac.png'));
        $this->assertFileExists(public_path('vendor/flag-icons/flags/4x3/ta.png'));
    }

    public function test_student_list_shows_the_total_across_all_pages(): void
    {
        $this->seedRolesAndAdmin();
        $class = SchoolClass::create(['name' => 'Grade 7', 'section' => 'A']);
        $names = [
            'Aisha Rahman', 'Mateo Silva', 'Mei Chen', 'Amara Okafor', 'Luca Rossi',
            'Sofia Alvarez', 'Noah Bennett', 'Leila Haddad', 'Arjun Mehta', 'Elena Petrova',
            'Kwame Mensah',
        ];

        foreach ($names as $index => $name) {
            $user = User::create([
                'name' => $name,
                'email' => 'student-list-'.$index.'@school.test',
                'password' => 'password',
                'role' => 'student',
            ]);
            Student::create([
                'user_id' => $user->id,
                'class_id' => $class->id,
                'admission_no' => sprintf('2026-G7-%04d', $index + 1),
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSeeText('Total matching students: 11');
    }

    public function test_guardian_email_and_phone_must_be_unique(): void
    {
        $this->seedRolesAndAdmin();
        [, $guardian] = $this->createEnrollmentContext('Grade 7', 'D');
        $guardian->update(['phone' => '+94771234567']);

        $this->actingAs($this->admin)
            ->post(route('admin.guardians.store'), [
                'name' => 'Aisha Rahman',
                'email' => $guardian->user->email,
                'password' => 'Password123!',
                'phone' => '0771234567',
                'phone_region' => 'LK',
            ])
            ->assertSessionHasErrors(['email', 'phone']);

        $this->assertSame(1, Guardian::count());
    }

    public function test_generic_user_management_cannot_create_unlinked_student_accounts(): void
    {
        $this->seedRolesAndAdmin();

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-person-name', false)
            ->assertSee('Campus');

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
