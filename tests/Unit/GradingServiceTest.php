<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentage_per_paper(): void
    {
        $this->assertSame(80.0, GradingService::percentage(80, 100));
        $this->assertSame(50.0, GradingService::percentage(45, 90));
        $this->assertSame(0.0, GradingService::percentage(null, 100));
        $this->assertSame(0.0, GradingService::percentage(50, 0));
    }

    public function test_grade_lookup_boundaries(): void
    {
        $this->assertSame('A', GradingService::grade(100));
        $this->assertSame('B', GradingService::grade(84.99));
        $this->assertSame('A', GradingService::grade(85));
        $this->assertSame('B', GradingService::grade(75));
        $this->assertSame('C', GradingService::grade(74.99));
        $this->assertSame('C', GradingService::grade(65));
        $this->assertSame('S', GradingService::grade(64.99));
        $this->assertSame('S', GradingService::grade(50));
        $this->assertSame('F', GradingService::grade(49.99));
        $this->assertSame('F', GradingService::grade(0));
        $this->assertSame(84.99, GradingService::percentage(84.99, 100));
    }

    public function test_grade_points(): void
    {
        $this->assertSame(3.0, GradingService::gradePoint(80));
        $this->assertSame(2.0, GradingService::gradePoint(70));
        $this->assertSame(1.0, GradingService::gradePoint(60));
        $this->assertSame(0.0, GradingService::gradePoint(45));
        $this->assertSame(0.0, GradingService::gradePoint(30));
    }

    public function test_calculate_term_gpa(): void
    {
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 4]);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG', 'credit_hours' => 3]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        $exam1 = Exam::create(['name' => 'E1', 'class_id' => $class->id, 'subject_id' => $maths->id, 'exam_date' => '2026-05-01', 'max_marks' => 100]);
        $exam2 = Exam::create(['name' => 'E2', 'class_id' => $class->id, 'subject_id' => $english->id, 'exam_date' => '2026-05-02', 'max_marks' => 100]);

        Result::create(['exam_id' => $exam1->id, 'student_id' => $student->id, 'marks' => 80]); // B (3.0) x 4 credits
        Result::create(['exam_id' => $exam2->id, 'student_id' => $student->id, 'marks' => 60]); // S (1.0) x 3 credits

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get();

        $this->assertSame(2.14, GradingService::calculateTermGpa($results));
    }

    public function test_term_gpa_uses_weighted_exam_percentage_once_per_subject(): void
    {
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 3]);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG', 'credit_hours' => 3]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 'weighted@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM-WEIGHTED',
        ]);

        $mathsAssessment = Exam::create([
            'name' => 'Maths assessment',
            'class_id' => $class->id,
            'subject_id' => $maths->id,
            'exam_date' => '2026-05-01',
            'max_marks' => 100,
            'weight' => 25,
        ]);
        $mathsFinal = Exam::create([
            'name' => 'Maths final',
            'class_id' => $class->id,
            'subject_id' => $maths->id,
            'exam_date' => '2026-05-02',
            'max_marks' => 100,
            'weight' => 75,
        ]);
        $englishFinal = Exam::create([
            'name' => 'English final',
            'class_id' => $class->id,
            'subject_id' => $english->id,
            'exam_date' => '2026-05-03',
            'max_marks' => 100,
            'weight' => 100,
        ]);

        Result::create(['exam_id' => $mathsAssessment->id, 'student_id' => $student->id, 'marks' => 100]);
        Result::create(['exam_id' => $mathsFinal->id, 'student_id' => $student->id, 'marks' => 60]);
        Result::create(['exam_id' => $englishFinal->id, 'student_id' => $student->id, 'marks' => 90]);

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get();

        $mathsResults = $results->filter(fn (Result $result) => $result->exam->subject_id === $maths->id);
        $this->assertSame(70.0, GradingService::calculateSubjectPercentage($mathsResults));
        $this->assertSame(3.0, GradingService::calculateTermGpa($results));
    }

    public function test_absent_result_scores_zero_in_gpa(): void
    {
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 4]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        $exam = Exam::create(['name' => 'E1', 'class_id' => $class->id, 'subject_id' => $maths->id, 'exam_date' => '2026-05-01', 'max_marks' => 100]);
        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => null, 'is_absent' => true]);

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get();

        // Absent = 0% => F => 0.0 grade points, but credits still count => GPA 0.0
        $this->assertSame(0.0, GradingService::calculateTermGpa($results));
    }

    public function test_null_marks_result_is_excluded_from_gpa(): void
    {
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 4]);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG', 'credit_hours' => 3]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $student = Student::create([
            'user_id' => User::create(['name' => 'S', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student'])->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM001',
        ]);

        $exam1 = Exam::create(['name' => 'E1', 'class_id' => $class->id, 'subject_id' => $maths->id, 'exam_date' => '2026-05-01', 'max_marks' => 100]);
        $exam2 = Exam::create(['name' => 'E2', 'class_id' => $class->id, 'subject_id' => $english->id, 'exam_date' => '2026-05-02', 'max_marks' => 100]);

        Result::create(['exam_id' => $exam1->id, 'student_id' => $student->id, 'marks' => 80]); // 3.0 x 4
        Result::create(['exam_id' => $exam2->id, 'student_id' => $student->id, 'marks' => null]); // excluded

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get();

        $this->assertSame(3.0, GradingService::calculateTermGpa($results));
    }

    public function test_no_results_returns_null_gpa(): void
    {
        $this->assertNull(GradingService::calculateTermGpa(collect()));
    }
}
