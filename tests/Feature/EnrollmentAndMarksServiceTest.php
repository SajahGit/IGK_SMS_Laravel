<?php

namespace Tests\Feature;

use App\Exceptions\MarksLockedException;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\MarksEntryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnrollmentAndMarksServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_service_creates_student_and_user(): void
    {
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'A', 5);

        $student = EnrollmentService::enrollStudent([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ]);

        $this->assertInstanceOf(Student::class, $student);
        $this->assertNotNull($student->user);
        $this->assertEquals('student', $student->user->role);
        $this->assertTrue($student->user->is_active);
        $this->assertTrue($student->user->must_change_password);
        $this->assertMatchesRegularExpression('/^2026-CS-\d{4}$/', $student->admission_no);
        $this->assertCount(1, $student->guardians);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.test']);
        $this->assertDatabaseHas('students', ['admission_no' => $student->admission_no]);
    }

    public function test_enrollment_automatically_enrols_student_in_class_subjects(): void
    {
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'A');
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $teacher = Teacher::create([
            'user_id' => User::create(['name' => 'Teacher', 'email' => 'teacher@example.test', 'password' => 'password', 'role' => 'teacher'])->id,
            'phone' => '555-0100',
        ]);
        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = EnrollmentService::enrollStudent([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ]);

        $this->assertDatabaseHas('student_subject_enrollments', [
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
        ]);
    }

    public function test_new_class_subject_is_assigned_to_already_enrolled_students(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->assertExitCode(0);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $student = Student::create([
            'user_id' => User::create(['name' => 'Student', 'email' => 'student@example.test', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM-010',
        ]);
        $teacher = Teacher::create([
            'user_id' => User::create(['name' => 'Teacher', 'email' => 'teacher@example.test', 'password' => 'password', 'role' => 'teacher'])->id,
            'phone' => '555-0100',
        ]);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password', 'role' => 'admin']);
        $admin->syncRoles(['Institution Admin']);

        $response = $this->actingAs($admin)->post(route('admin.class-subjects.store'), [
            'class_id' => $class->id,
            'term_id' => Term::create([
                'academic_year_id' => AcademicYear::create([
                    'name' => '2026',
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                ])->id,
                'name' => 'Term 1',
                'sequence' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-04-30',
            ])->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect();
        $classSubject = ClassSubject::where('class_id', $class->id)->where('subject_id', $subject->id)->firstOrFail();
        $this->assertDatabaseHas('student_subject_enrollments', [
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
        ]);
    }

    public function test_enrollment_service_enforces_class_capacity(): void
    {
        [$class, $guardian] = $this->createEnrollmentContext('Tiny Class', 'A', 1);

        EnrollmentService::enrollStudent([
            'name' => 'Student 1',
            'email' => 's1@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ]);

        $this->expectException(ValidationException::class);
        EnrollmentService::enrollStudent([
            'name' => 'Student 2',
            'email' => 's2@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ]);
    }

    public function test_enrollment_service_rejects_duplicate_admission_number(): void
    {
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'A');

        EnrollmentService::enrollStudent([
            'name' => 'Student 1',
            'email' => 's1@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'admission_no' => '2026-CS-0100',
        ]);

        $this->expectException(ValidationException::class);
        EnrollmentService::enrollStudent([
            'name' => 'Student 2',
            'email' => 's2@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'admission_no' => '2026-CS-0100',
        ]);
    }

    public function test_enrollment_service_drop_student_removes_record(): void
    {
        [$class, $guardian] = $this->createEnrollmentContext('Grade 10', 'A');

        $student = EnrollmentService::enrollStudent([
            'name' => 'Temp Student',
            'email' => 'temp@example.test',
            'password' => 'SecretPass123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ]);

        $userId = $student->user_id;
        $studentId = $student->id;

        EnrollmentService::dropStudent($student);

        $this->assertNull(Student::find($studentId));
        $this->assertNull(User::find($userId));
    }

    public function test_marks_entry_service_saves_marks(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => false,
        ]);

        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        MarksEntryService::bulkSave($exam, [$student->id => 85], []);

        $this->assertDatabaseHas('results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks' => 85.00,
            'is_absent' => false,
        ]);
    }

    public function test_marks_entry_service_marks_absent(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => false,
        ]);

        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        MarksEntryService::bulkSave($exam, [], [$student->id => true]);

        $this->assertDatabaseHas('results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks' => null,
            'is_absent' => true,
        ]);
    }

    public function test_marks_entry_service_rejects_when_published(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => true,
        ]);

        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        $this->expectException(MarksLockedException::class);
        MarksEntryService::bulkSave($exam, [$student->id => 90], []);
    }

    public function test_marks_entry_service_upserts_existing_result(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => false,
        ]);

        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => 50]);

        MarksEntryService::bulkSave($exam, [$student->id => 90], []);

        $this->assertDatabaseHas('results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks' => 90.00,
        ]);
        $this->assertCount(1, Result::where('exam_id', $exam->id)->where('student_id', $student->id)->get());
    }

    /**
     * @return array{SchoolClass, Guardian}
     */
    private function createEnrollmentContext(string $name, string $section, int $capacity = 40): array
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
            'capacity' => $capacity,
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $guardianUser = User::create([
            'name' => 'Student Guardian',
            'email' => 'guardian@example.test',
            'password' => 'GuardianPass123!',
            'role' => 'parent',
        ]);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '555-0101']);

        return [$class, $guardian];
    }
}
