<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Support\SchoolHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3MarksLockGpaPublicationReportCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_lock_when_exam_is_published(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);
        $tUser = User::create(['name' => 'Teacher', 'email' => 't@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $tUser->id, 'phone' => '111']);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $classSubject = ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        $stUser = User::create(['name' => 'Student', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $stUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);
        $classSubject->students()->attach($student->id, ['status' => 'enrolled']);

        $exam = Exam::create([
            'name' => 'Mid Term',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => false,
        ]);

        // Teacher can update marks when not published
        $resp1 = $this->actingAs($tUser)->post(route('teacher.marks.update', $exam), [
            'marks' => [$student->id => 85],
        ]);
        $resp1->assertRedirect(route('teacher.marks.show', $exam));
        $this->assertEquals(85, Result::where('exam_id', $exam->id)->first()->marks);

        // Admin publishes exam
        $pubResp = $this->actingAs($admin)->post(route('admin.exams.publish', $exam));
        $pubResp->assertSessionHasNoErrors();
        $exam->refresh();
        $this->assertTrue($exam->is_published);

        // Teacher now blocked from modifying marks
        $resp2 = $this->actingAs($tUser)->post(route('teacher.marks.update', $exam), [
            'marks' => [$student->id => 90],
        ]);
        $resp2->assertStatus(403);
    }

    public function test_students_and_guardians_can_only_view_published_results(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'History', 'code' => 'HIS']);

        $pUser = User::create(['name' => 'Parent', 'email' => 'p@s.t', 'password' => 'password', 'role' => 'parent']);
        $guardian = Guardian::create(['user_id' => $pUser->id, 'phone' => '123']);

        $stUser = User::create(['name' => 'Student', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $stUser->id, 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'admission_no' => 'ADM001']);

        $examUnpublished = Exam::create([
            'name' => 'Draft Exam',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-05-01',
            'max_marks' => 100,
            'is_published' => false,
        ]);
        Result::create(['exam_id' => $examUnpublished->id, 'student_id' => $student->id, 'marks' => 70]);

        $examPublished = Exam::create([
            'name' => 'Final Exam',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-06-01',
            'max_marks' => 100,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $examPublished->id, 'student_id' => $student->id, 'marks' => 95]);

        // Student results view
        $studentView = $this->actingAs($stUser)->get(route('student.results'));
        $studentView->assertStatus(200);
        $studentView->assertSee('Final Exam');
        $studentView->assertDontSee('Draft Exam');

        // Guardian results view
        $parentView = $this->actingAs($pUser)->get(route('guardian.results', $student));
        $parentView->assertStatus(200);
        $parentView->assertSee('Final Exam');
        $parentView->assertDontSee('Draft Exam');
    }

    public function test_gpa_calculation_formula(): void
    {
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 4]);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG', 'credit_hours' => 3]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $studentUser = User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        // Exam 1: Maths (4 credits), score: 80% (Grade A => 4.0 GP)
        $exam1 = Exam::create(['name' => 'E1', 'class_id' => $class->id, 'subject_id' => $maths->id, 'exam_date' => '2026-05-01', 'max_marks' => 100]);
        $r1 = Result::create(['exam_id' => $exam1->id, 'student_id' => $student->id, 'marks' => 80]);

        // Exam 2: English (3 credits), score: 60% (Grade C => 2.0 GP)
        $exam2 = Exam::create(['name' => 'E2', 'class_id' => $class->id, 'subject_id' => $english->id, 'exam_date' => '2026-05-02', 'max_marks' => 100]);
        $r2 = Result::create(['exam_id' => $exam2->id, 'student_id' => $student->id, 'marks' => 60]);

        // Expected GPA: (3.0 * 4 + 1.0 * 3) / (4 + 3) = 15 / 7 = 2.14
        $results = collect([$r1->load('exam.subject'), $r2->load('exam.subject')]);
        $gpa = SchoolHelper::calculateTermGpa($results);

        $this->assertEquals(2.14, $gpa);
    }

    public function test_printable_report_card_view(): void
    {
        $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-04-30', 'is_current' => true]);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 4]);

        $stUser = User::create(['name' => 'Student Top', 'email' => 'top@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $stUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $exam = Exam::create([
            'name' => 'Term 1 Final',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-04-10',
            'max_marks' => 100,
            'is_published' => true,
        ]);
        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => 88]);

        $response = $this->actingAs($stUser)->get(route('student.report-card'));
        $response->assertStatus(200);
        $response->assertSee('Academic Report Card');
        $response->assertSee('Student Top');
        $response->assertSee('Term Grade Point Average (GPA)');
        $response->assertSee('Class Teacher');
    }
}
