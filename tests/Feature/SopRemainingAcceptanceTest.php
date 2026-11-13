<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\AttendanceSessionService;
use App\Services\MarksEntryService;
use App\Services\ResultPublicationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SopRemainingAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_admission_requires_explicit_confirmation_and_lists_match(): void
    {
        $admin = $this->seedAdmin();
        [$class, $program, $year, $guardian] = $this->createAcademicContext();
        $existingUser = User::create([
            'name' => 'Same Student',
            'email' => 'same-existing@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        Student::create([
            'user_id' => $existingUser->id,
            'class_id' => $class->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'admission_no' => '2026-CS-0001',
            'dob' => '2010-05-15',
        ]);
        $admission = [
            'name' => 'Same Student',
            'email' => 'same-new@school.test',
            'password' => 'StrongPassword123!',
            'class_id' => $class->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'guardian_ids' => [$guardian->id],
            'dob' => '2010-05-15',
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'DUPL001',
        ];

        $this->actingAs($admin)
            ->post(route('admin.students.store'), $admission)
            ->assertSessionHasErrors('duplicate_confirmation')
            ->assertSessionHas('possible_duplicates');
        $this->assertSame('2026-CS-0001', session('possible_duplicates')[0]['admission_no']);

        $this->assertSame(1, Student::where('class_id', $class->id)->count());
        $this->assertDatabaseMissing('users', ['email' => 'same-new@school.test']);

        $this->actingAs($admin)
            ->post(route('admin.students.store'), $admission + ['confirm_duplicate' => 1])
            ->assertRedirect(route('admin.students.index'));

        $this->assertSame(2, Student::where('class_id', $class->id)->count());
        $this->assertDatabaseHas('users', ['email' => 'same-new@school.test']);
    }

    public function test_student_document_number_is_unique(): void
    {
        $admin = $this->seedAdmin();
        [$class, $program, $year, $guardian] = $this->createAcademicContext();
        $base = [
            'class_id' => $class->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'guardian_ids' => [$guardian->id],
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'DUPL002',
        ];

        $this->actingAs($admin)->post(route('admin.students.store'), $base + [
            'name' => 'First Student',
            'email' => 'first-national-id@school.test',
            'password' => 'StrongPassword123!',
            'dob' => '2010-05-15',
        ])->assertRedirect(route('admin.students.index'));

        $this->actingAs($admin)->post(route('admin.students.store'), $base + [
            'name' => 'Second Student',
            'email' => 'second-national-id@school.test',
            'password' => 'StrongPassword123!',
            'dob' => '2011-06-16',
        ])->assertSessionHasErrors('document_number');

        $this->assertDatabaseMissing('users', ['email' => 'second-national-id@school.test']);
    }

    public function test_subject_enrollment_status_scopes_attendance_marks_and_register_report(): void
    {
        $admin = $this->seedAdmin();
        [$class, $program, $year, $guardian, $term] = $this->createAcademicContext();
        $teacherUser = User::create(['name' => 'Teacher', 'email' => 'enrollment-teacher@school.test', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'phone' => '555-0101']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $enrolled = $this->student($class, 'Enrolled Student', 'enrolled@school.test');
        $dropped = $this->student($class, 'Dropped Student', 'dropped@school.test');
        $classSubject->students()->attach($enrolled->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
        $classSubject->students()->attach($dropped->id, ['status' => 'dropped', 'enrolled_at' => now(), 'dropped_at' => now()]);

        $this->assertSame([$enrolled->id], AttendanceSessionService::enrolledStudentIds($class->id, $classSubject->id)->all());

        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => today()->toDateString(),
            'max_marks' => 100,
        ]);
        $this->assertSame([$enrolled->id], MarksEntryService::enrolledStudentIds($exam)->all());
        MarksEntryService::bulkSave($exam, [
            $enrolled->id => 88,
            $dropped->id => 99,
        ], []);
        $this->assertDatabaseHas('results', ['exam_id' => $exam->id, 'student_id' => $enrolled->id]);
        $this->assertDatabaseMissing('results', ['exam_id' => $exam->id, 'student_id' => $dropped->id]);
        ResultPublicationService::publish($exam);
        $this->assertTrue($exam->fresh()->is_published);

        $this->actingAs($admin)
            ->get(route('reports.enrollment', ['class_id' => $class->id, 'status' => 'dropped']))
            ->assertOk()
            ->assertSee('Dropped Student')
            ->assertDontSee('Enrolled Student');

        $this->actingAs($admin)
            ->post(route('admin.students.class-subjects.drop', [$enrolled, $classSubject]))
            ->assertRedirect();

        $this->assertDatabaseHas('student_subject_enrollments', [
            'student_id' => $enrolled->id,
            'class_subject_id' => $classSubject->id,
            'status' => 'dropped',
        ]);
        $this->assertSame([], AttendanceSessionService::enrolledStudentIds($class->id, $classSubject->id)->all());
    }

    public function test_r5_subject_summary_uses_only_enrolled_students_and_exact_date_window(): void
    {
        $admin = $this->seedAdmin();
        [$class, , , , $term] = $this->createAcademicContext();
        $teacherUser = User::create(['name' => 'Teacher', 'email' => 'r5-teacher@school.test', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'phone' => '555-0102']);
        $subject = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $present = $this->student($class, 'Present Student', 'r5-present@school.test');
        $absent = $this->student($class, 'Absent Student', 'r5-absent@school.test');
        $dropped = $this->student($class, 'Dropped Student', 'r5-dropped@school.test');
        $classSubject->students()->attach($present->id, ['status' => 'enrolled']);
        $classSubject->students()->attach($absent->id, ['status' => 'enrolled']);
        $classSubject->students()->attach($dropped->id, ['status' => 'dropped']);
        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'date' => today()->toDateString(),
            'start_time' => '08:00',
            'session_label' => 'Science',
            'taken_by' => $admin->id,
        ]);
        foreach ([[$present, 'present'], [$absent, 'absent']] as [$student, $status]) {
            Attendance::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'attendance_session_id' => $session->id,
                'date' => today()->toDateString(),
                'status' => $status,
                'marked_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('reports.class-attendance', [
                'class_id' => $class->id,
                'term_id' => $term->id,
                'subject_id' => $subject->id,
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
                'threshold' => 75,
            ]))
            ->assertOk()
            ->assertSee('50%')
            ->assertSee('Students Below 75%')
            ->assertSee('<td class="text-center">1</td>', false)
            ->assertDontSee('<td class="text-center">2</td>', false);
    }

    public function test_r10_overdue_only_and_minimum_balance_filters_match_csv_data(): void
    {
        $admin = $this->seedAdmin();
        [$class, $program, , , $term] = $this->createAcademicContext();
        $student = $this->student($class, 'Fee Student', 'r10-student@school.test');
        $invoices = [
            ['INV-OVERDUE', today()->subDays(10), '1000.00'],
            ['INV-DUE-TODAY', today(), '100.00'],
            ['INV-FUTURE', today()->addDays(10), '200.00'],
        ];
        foreach ($invoices as [$invoiceNo, $dueDate, $balance]) {
            Invoice::create([
                'student_id' => $student->id,
                'term_id' => $term->id,
                'invoice_no' => $invoiceNo,
                'issue_date' => today()->subDays(20),
                'due_date' => $dueDate,
                'total' => $balance,
                'amount_paid' => '0.00',
                'balance' => $balance,
                'status' => 'issued',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('reports.defaulters', [
                'term_id' => $term->id,
                'program_id' => $program->id,
                'class_id' => $class->id,
                'overdue_only' => 1,
                'min_balance' => 500,
            ]))
            ->assertOk()
            ->assertSee('INV-OVERDUE')
            ->assertDontSee('INV-DUE-TODAY')
            ->assertDontSee('INV-FUTURE');

        $csv = $this->get(route('reports.defaulters.csv', [
            'term_id' => $term->id,
            'program_id' => $program->id,
            'class_id' => $class->id,
            'overdue_only' => 1,
            'min_balance' => 500,
        ]))->assertOk()->streamedContent();

        $this->assertStringContainsString(
            '"Invoice No",Term,Program,"Student Name",Class,"Parent / Contact","Due Date","Total Billed","Amount Paid","Balance Due","Days Overdue"',
            $csv
        );
        $this->assertStringContainsString('INV-OVERDUE', $csv);
        $this->assertStringContainsString(today()->subDays(10)->toDateString().',1000.00,0.00,1000.00,10', $csv);
        $this->assertStringNotContainsString('INV-DUE-TODAY', $csv);
    }

    private function seedAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'sop-acceptance-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $admin->syncRoles(['Super Admin']);

        return $admin;
    }

    private function createAcademicContext(): array
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'CS']);
        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        $class = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'capacity' => 40,
        ]);
        $guardianUser = User::create([
            'name' => 'Guardian',
            'email' => 'sop-acceptance-guardian@school.test',
            'password' => 'password',
            'role' => 'parent',
        ]);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '555-0199']);

        return [$class, $program, $year, $guardian, $term];
    }

    private function student(SchoolClass $class, string $name, string $email): Student
    {
        return Student::create([
            'user_id' => User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'role' => 'student',
            ])->id,
            'class_id' => $class->id,
            'program_id' => $class->program_id,
            'academic_year_id' => $class->academic_year_id,
            'admission_no' => 'ADM-'.str_replace('@school.test', '', $email),
            'dob' => '2010-05-15',
        ]);
    }
}
