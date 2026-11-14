<?php

namespace Tests\Feature;

use App\Http\Controllers\Report\StudentReportController;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\TermResultService;
use Database\Seeders\GradeScaleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsParityTest extends TestCase
{
    use RefreshDatabase;

    private function csvHeader($response): array
    {
        return str_getcsv(strtok(trim($response->streamedContent()), "\r\n"));
    }

    private function seedAndAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $user = User::updateOrCreate(
            ['email' => 'superadmin@school.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $user->syncRoles(['Super Admin']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    public function test_r1_csv_header_matches_student_register_columns(): void
    {
        $this->actingAs($this->seedAndAdmin());

        $response = $this->get(route('reports.students.csv'))->assertOk();

        $this->assertSame([
            '#', 'Admission No', 'Name', 'Program', 'Section', 'Status', 'Admission Date', 'Guardian Phone',
        ], $this->csvHeader($response));
    }

    public function test_student_pdf_report_caps_large_exports_at_five_hundred_students(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $now = now()->toDateTimeString();
        $userRows = [];

        for ($index = 1; $index <= 1001; $index++) {
            $userRows[] = [
                'name' => 'PDF Student '.$index,
                'email' => 'pdf-cap-'.$index.'@school.test',
                'password' => 'unused-test-password',
                'role' => 'student',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($userRows, 100) as $batch) {
            DB::table('users')->insert($batch);
        }

        $userIds = DB::table('users')
            ->where('email', 'like', 'pdf-cap-%@school.test')
            ->orderBy('id')
            ->pluck('id');
        $studentRows = [];

        foreach ($userIds as $offset => $userId) {
            $studentRows[] = [
                'user_id' => $userId,
                'class_id' => $class->id,
                'admission_no' => sprintf('PDF-%04d', $offset + 1),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($studentRows, 100) as $batch) {
            DB::table('students')->insert($batch);
        }

        $request = Request::create('/reports/students/pdf');
        $request->setUserResolver(fn () => $admin);
        $reportView = app(StudentReportController::class)->pdf($request);
        $viewData = $reportView->getData();

        $this->assertCount(500, $viewData['students']);

        $this->get(route('reports.pdf', ['report' => 'students']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_fee_collection_and_defaulters_pdfs_render_with_limited_relations(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $studentUser = User::create([
            'name' => 'PDF Finance Student',
            'email' => 'pdf-finance-student@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'admission_no' => 'PDF-FIN-001',
        ]);
        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'PDF-INV-001',
            'issue_date' => today()->subDays(20),
            'due_date' => today()->subDays(10),
            'total' => '100.00',
            'amount_paid' => '25.00',
            'balance' => '75.00',
            'status' => 'partially_paid',
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'PDF-RCP-001',
            'amount' => '25.00',
            'method' => 'cash',
            'paid_at' => today(),
            'received_by' => $admin->id,
        ]);

        foreach (['fee-collection', 'defaulters'] as $report) {
            $this->get(route('reports.pdf', ['report' => $report]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_r2_enrollment_register_csv_streams(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $tUser = User::create(['name' => 'Teacher', 'email' => 't@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $tUser->id, 'phone' => '111']);
        $classSubject = $classSubject = ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        $studentUser = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);
        $classSubject->students()->attach($student->id);

        $resp = $this->get(route('reports.enrollment.csv', ['class_id' => $class->id]));
        $resp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));
        $this->assertSame([
            'Term', 'Program', 'Section', 'Subject', 'Teacher', 'Student Count', 'Student List',
        ], $this->csvHeader($resp));

        $this->get(route('reports.enrollment', ['class_id' => $class->id]))
            ->assertStatus(200)
            ->assertSee('Maths')
            ->assertSee('ADM001')
            ->assertSee('Student Count');
    }

    public function test_r2_enrollment_register_requires_academic_permission(): void
    {
        $this->seedAndAdmin();
        $tUser = User::create(['name' => 'Teacher', 'email' => 't@s.t', 'password' => 'password', 'role' => 'teacher']);
        // No Spatie role synced => no reports.academic permission
        $this->actingAs($tUser);

        $this->get(route('reports.enrollment'))->assertStatus(403);
        $this->get(route('reports.enrollment.csv'))->assertStatus(403);
    }

    public function test_r6_exam_report_csv_header_matches_visible_table(): void
    {
        $this->actingAs($this->seedAndAdmin());

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $exam = Exam::create([
            'name' => 'Final',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => today()->toDateString(),
            'max_marks' => 100,
        ]);

        $response = $this->get(route('reports.results.csv', ['exam_id' => $exam->id]))->assertOk();
        $csvRows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));

        $this->assertSame([
            'Rank', 'Admission No', 'Name', 'Marks', 'Maximum', 'Percentage', 'Grade', 'Absent',
        ], $csvRows[1]);
        $this->get(route('reports.pdf', ['report' => 'results', 'exam_id' => $exam->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_r6_exam_marks_report_requires_academic_permission(): void
    {
        $this->seedAndAdmin();
        $teacherRole = Role::findByName('Teacher');
        $teacherRole->revokePermissionTo('reports.academic');
        $teacher = User::create(['name' => 'Limited Teacher', 'email' => 'limited-report-teacher@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher->syncRoles(['Teacher']);

        $this->actingAs($teacher)
            ->get(route('reports.results'))
            ->assertForbidden();
        $this->get(route('reports.results.csv'))
            ->assertForbidden();
    }

    public function test_r4_attendance_summary_per_student_csv_streams(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $studentUser = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => today()->toDateString(),
            'status' => 'present',
            'marked_by' => $admin->id,
        ]);

        $resp = $this->get(route('reports.attendance-summary.csv', ['class_id' => $class->id]));
        $resp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));
        $this->assertSame([
            'Admission No', 'Student', 'Sessions Held', 'Present', 'Absent', 'Late', 'Excused', 'Attendance %',
        ], $this->csvHeader($resp));

        $this->get(route('reports.attendance-summary', ['class_id' => $class->id]))
            ->assertStatus(200)
            ->assertSee('ADM001');
    }

    public function test_r7_class_result_summary_csv_streams(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $teacherUser = User::create(['name' => 'Teacher', 'email' => 'r7-teacher@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'phone' => '555']);
        $studentUser = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

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
        $offering = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $offering->students()->attach($student->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
        $exam = Exam::create([
            'name' => 'Final',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'weight' => 75,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => 85]);
        $assessment = Exam::create([
            'name' => 'Assessment',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-05-01',
            'max_marks' => 100,
            'weight' => 25,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $assessment->id, 'student_id' => $student->id, 'marks' => 65]);

        $secondClass = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);
        $secondOffering = ClassSubject::create([
            'class_id' => $secondClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $secondStudentUser = User::create(['name' => 'S2', 'email' => 's2@s.t', 'password' => 'password', 'role' => 'student']);
        $secondStudent = Student::create(['user_id' => $secondStudentUser->id, 'class_id' => $secondClass->id, 'admission_no' => 'ADM002']);
        $secondOffering->students()->attach($secondStudent->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
        $secondExam = Exam::create([
            'name' => 'Final',
            'class_id' => $secondClass->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $secondExam->id, 'student_id' => $secondStudent->id, 'marks' => 70]);

        $termResults = app(TermResultService::class);
        foreach ([$class, $secondClass] as $resultClass) {
            $termResults->generate($term, $resultClass->id);
            $termResults->submitForApproval($term, $resultClass->id, $admin);
            $termResults->publishClass($term, $resultClass->id, $admin);
        }

        $resp = $this->get(route('reports.class-result.csv', ['term_id' => $term->id]));
        $resp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));
        $csvRows = array_map('str_getcsv', explode("\n", trim($resp->streamedContent())));
        $this->assertSame('Rank in section', $csvRows[0][0]);
        $this->assertSame('1', $csvRows[1][0]);
        $this->assertSame('1', $csvRows[2][0]);
        $this->assertSame('B', $csvRows[1][4]);
        $this->assertSame('3', $csvRows[1][5]);

        $this->get(route('reports.class-result', ['term_id' => $term->id]))
            ->assertStatus(200)
            ->assertSee('ADM001')
            ->assertSee('ADM002')
            ->assertSee('80');
    }

    public function test_r11_student_financial_ledger_csv_streams(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $studentUser = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'INV-2026-0001',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'total' => '10000.00',
            'amount_paid' => '0.00',
            'balance' => '10000.00',
            'status' => 'issued',
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'RCP-2026-0001',
            'amount' => '4000.00',
            'method' => 'cash',
            'paid_at' => '2026-05-10',
            'received_by' => $admin->id,
        ]);

        $resp = $this->get(route('reports.student-ledger.csv', ['student_id' => $student->id]));
        $resp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));

        $this->get(route('reports.student-ledger', ['student_id' => $student->id]))
            ->assertStatus(200)
            ->assertSee('ADM001')
            ->assertSee('LKR 10,000.00');
        $this->assertSame(
            ['Date', 'Description', 'Type', 'Charge', 'Payment', 'Currency', 'Running Balance'],
            $this->csvHeader($resp)
        );
    }

    public function test_r11_financial_ledger_requires_financial_permission(): void
    {
        $this->seedAndAdmin();
        $tUser = User::create(['name' => 'Teacher', 'email' => 't2@s.t', 'password' => 'password', 'role' => 'teacher']);
        $this->actingAs($tUser);

        $this->get(route('reports.student-ledger'))->assertStatus(403);
        $this->get(route('reports.student-ledger.csv'))->assertStatus(403);
    }

    public function test_r3_r5_and_r8_reports_apply_session_term_and_subject_filters(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $teacherUser = User::create(['name' => 'Teacher', 'email' => 'report-teacher@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'phone' => '111']);
        $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);
        $offering = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $studentUser = User::create(['name' => 'S1', 'email' => 'report-student@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM-RPT']);
        $offering->students()->attach($student->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'class_subject_id' => $offering->id,
            'term_id' => $term->id,
            'date' => today()->toDateString(),
            'start_time' => '08:15',
            'session_label' => 'Mathematics',
            'taken_by' => $admin->id,
        ]);
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_session_id' => $session->id,
            'date' => today()->toDateString(),
            'status' => 'present',
            'remarks' => 'On time',
            'marked_by' => $admin->id,
        ]);
        $exam = Exam::create([
            'name' => 'Final',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => today()->toDateString(),
            'max_marks' => 100,
            'weight' => 75,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => 85]);
        $mathsAssessment = Exam::create([
            'name' => 'Assessment',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => today()->toDateString(),
            'max_marks' => 100,
            'weight' => 25,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $mathsAssessment->id, 'student_id' => $student->id, 'marks' => 65]);
        $biology = Subject::create(['name' => 'Biology', 'code' => 'BIO']);
        $biologyExam = Exam::create([
            'name' => 'Final',
            'class_id' => $class->id,
            'subject_id' => $biology->id,
            'term_id' => $term->id,
            'exam_date' => today()->toDateString(),
            'max_marks' => 100,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $biologyExam->id, 'student_id' => $student->id, 'marks' => 70]);

        $termResultService = app(TermResultService::class);
        $termResultService->generate($term, $class->id);
        $termResultService->submitForApproval($term, $class->id, $admin);
        $termResultService->publishClass($term, $class->id, $admin);

        $this->assertSame(1, AttendanceSession::where('class_id', $class->id)->whereDate('date', today())->count());
        $this->assertSame(1, $session->records()->count());
        $this->assertCount(1, AttendanceSession::with('records')->findOrFail($session->id)->records);
        $this->get(route('reports.attendance', [
            'class_id' => $class->id,
            'from' => today()->subDays(1)->toDateString(),
            'to' => today()->toDateString(),
        ]))
            ->assertOk()->assertSee('ADM-RPT')->assertSee('On time')->assertSee('Maths')->assertSee('08:15');
        $attendanceCsv = $this->get(route('reports.attendance.csv', [
            'class_id' => $class->id,
            'from' => today()->subDays(1)->toDateString(),
            'to' => today()->toDateString(),
        ]))->assertOk();
        $this->assertSame([
            'Date', 'Class/Section', 'Subject', 'Teacher', 'Admission No', 'Student', 'Status', 'Remarks', 'Taken by', 'Time',
        ], $this->csvHeader($attendanceCsv));
        $this->get(route('reports.class-attendance', [
            'class_id' => $class->id,
            'term_id' => $term->id,
            'subject_id' => $subject->id,
        ]))->assertOk()->assertSee('1')->assertSee('100%');
        $this->get(route('reports.pdf', ['report' => 'class-attendance', 'class_id' => $class->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $classAttendanceCsv = $this->get(route('reports.class-attendance.csv', [
            'class_id' => $class->id,
            'term_id' => $term->id,
            'subject_id' => $subject->id,
        ]))->assertOk();
        $this->assertSame([
            'Class & Section', 'Subject', 'Sessions Held', 'Average Attendance', 'Students Below 75%',
        ], $this->csvHeader($classAttendanceCsv));
        $this->get(route('reports.subject-performance', [
            'class_id' => $class->id,
            'term_id' => $term->id,
            'subject_id' => $subject->id,
        ]))->assertOk()->assertSee('Maths')->assertSee('80%')->assertSee('0/1/0/0/0');
        $this->get(route('reports.pdf', ['report' => 'subject-performance', 'class_id' => $class->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->artisan('db:seed', ['--class' => GradeScaleSeeder::class])->run();
        $subjectCsv = $this->get(route('reports.subject-performance.csv', [
            'class_id' => $class->id,
            'term_id' => $term->id,
            'subject_id' => $subject->id,
        ]));
        $subjectCsv->assertOk();
        $subjectHeader = $this->csvHeader($subjectCsv);
        $this->assertSame([
            'Subject', 'Credit Hours', 'Total Exams', 'Students', 'Average (%)', 'Pass Rate',
            'Highest (%)', 'Lowest (%)',
        ], array_slice($subjectHeader, 0, 8));
        $this->assertStringStartsWith('Grade Distribution (', $subjectHeader[8]);
        $this->assertStringContainsString('Maths (MTH)', $subjectCsv->streamedContent());
        $this->assertStringNotContainsString('BIO,Biology', $subjectCsv->streamedContent());
    }

    public function test_r9_and_r10_csv_headers_match_visible_report_columns(): void
    {
        $this->actingAs($this->seedAndAdmin());

        $feeCollection = $this->get(route('reports.fee-collection.csv'))->assertOk();
        $this->assertSame([
            'Receipt No', 'Date', 'Term', 'Program', 'Invoice No', 'Student Details', 'Class',
            'Amount', 'Currency', 'Method', 'Reference', 'Cashier', 'Status',
        ], $this->csvHeader($feeCollection));

        $defaulters = $this->get(route('reports.defaulters.csv'))->assertOk();
        $this->assertSame([
            'Invoice No', 'Term', 'Program', 'Student Name', 'Class', 'Parent / Contact',
            'Due Date', 'Total Billed', 'Amount Paid', 'Balance Due', 'Currency', 'Days Overdue',
        ], $this->csvHeader($defaulters));
    }

    public function test_student_ledger_is_limited_to_the_signed_in_student(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $ownUser = User::create(['name' => 'Own Student', 'email' => 'own-ledger@s.t', 'password' => 'password', 'role' => 'student']);
        $ownUser->syncRoles(['Student']);
        $ownStudent = Student::create(['user_id' => $ownUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM-OWN']);
        $otherUser = User::create(['name' => 'Other Student', 'email' => 'other-ledger@s.t', 'password' => 'password', 'role' => 'student']);
        $otherStudent = Student::create(['user_id' => $otherUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM-OTHER']);

        foreach ([$ownStudent, $otherStudent] as $index => $student) {
            Invoice::create([
                'student_id' => $student->id,
                'invoice_no' => 'INV-LEDGER-'.$index,
                'issue_date' => '2026-05-01',
                'due_date' => '2026-05-31',
                'total' => '1000.00',
                'amount_paid' => '0.00',
                'balance' => '1000.00',
                'status' => 'issued',
            ]);
        }

        $this->actingAs($ownUser)
            ->get(route('student.ledger', ['student_id' => $ownStudent->id]))
            ->assertOk()
            ->assertSee('ADM-OWN')
            ->assertDontSee('ADM-OTHER');
        $this->get(route('student.ledger', ['student_id' => $otherStudent->id]))->assertForbidden();
    }

    public function test_reports_handle_empty_data_without_error(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);

        $this->get(route('reports.enrollment', ['class_id' => $class->id]))->assertStatus(200);
        $this->get(route('reports.attendance-summary', ['class_id' => $class->id]))->assertStatus(200);
        $this->get(route('reports.class-result', ['class_id' => $class->id]))->assertStatus(200);
        $this->get(route('reports.student-ledger'))->assertStatus(200);
    }
}
