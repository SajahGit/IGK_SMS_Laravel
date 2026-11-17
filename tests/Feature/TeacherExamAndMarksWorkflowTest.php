<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Program;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherExamAndMarksWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;
    private SchoolClass $class;
    private Subject $subject;
    private Term $term;
    private ClassSubject $classSubject;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $branch = Branch::create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
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

        $this->term = Term::create([
            'name' => 'Term 1',
            'academic_year_id' => $academicYear->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_current' => true,
        ]);

        $dept = Department::create([
            'name' => 'Sciences',
            'code' => 'SCI',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Secondary Education',
            'code' => 'SEC',
            'department_id' => $dept->id,
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

        $this->subject = Subject::create([
            'name' => 'Physics',
            'code' => 'PHY10',
        ]);

        $this->teacherUser = User::factory()->create([
            'name' => 'Dr. Aris Thorne',
            'email' => 'aris.thorne@school.test',
            'role' => 'teacher',
            'branch_id' => $branch->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->teacherUser->syncRoles(['Teacher']);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $this->classSubject = ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'term_id' => $this->term->id,
        ]);

        // Student in the class
        $studentUser = User::factory()->create([
            'name' => 'Ali Imran',
            'email' => 'ali.imran@school.test',
            'role' => 'student',
            'branch_id' => $branch->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $studentUser->syncRoles(['Student']);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'branch_id' => $branch->id,
            'admission_no' => '2026-SEC-0010',
            'status' => 'active',
        ]);
    }

    public function test_teacher_exams_create_renders_cleanly_without_null_property_errors(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.exams.create'));

        $response->assertOk();
        $response->assertSee('Create exam');
        $response->assertSee($this->class->full_name);
        $response->assertSee($this->subject->name);
    }

    public function test_teacher_can_create_exam_and_enter_marks_workflow(): void
    {
        // 1. Create exam
        $createResponse = $this->actingAs($this->teacherUser)
            ->post(route('teacher.exams.store'), [
                'name' => 'Midterm Physics Assessment',
                'assignment' => $this->classSubject->id,
                'exam_date' => '2026-03-15',
                'max_marks' => 100,
            ]);

        $createResponse->assertRedirect(route('teacher.exams.index'));

        $exam = Exam::where('name', 'Midterm Physics Assessment')->firstOrFail();
        $this->assertSame($this->class->id, $exam->class_id);
        $this->assertSame($this->subject->id, $exam->subject_id);

        // 2. Open Enter Marks page
        $editResponse = $this->actingAs($this->teacherUser)
            ->get(route('teacher.marks.edit', $exam));

        $editResponse->assertOk();
        $editResponse->assertSee('Enter Marks');
        $editResponse->assertSee('Ali Imran');
        $editResponse->assertSee('Save Marks');

        // 3. Submit Marks
        $updateResponse = $this->actingAs($this->teacherUser)
            ->post(route('teacher.marks.update', $exam), [
                'marks' => [
                    $this->student->id => 88.5,
                ],
            ]);

        $updateResponse->assertRedirect(route('teacher.marks.show', $exam));

        $this->assertDatabaseHas('results', [
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'marks' => 88.5,
            'is_absent' => false,
        ]);

        // 4. View Results
        $showResponse = $this->actingAs($this->teacherUser)
            ->get(route('teacher.marks.show', $exam));

        $showResponse->assertOk();
        $showResponse->assertSee('88.5');
        $showResponse->assertSee('Ali Imran');
    }

    public function test_newly_created_teacher_exam_appears_at_the_very_top_of_the_list(): void
    {
        // Create an older exam with a future exam_date
        Exam::create([
            'name' => 'Future End-Year Physics Finals',
            'type' => 'final',
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'term_id' => $this->term->id,
            'exam_date' => '2026-12-15',
            'max_marks' => 100,
            'weight' => 100,
            'created_by' => $this->teacherUser->id,
        ]);

        // Now teacher creates a new exam with an earlier exam_date
        $this->actingAs($this->teacherUser)
            ->post(route('teacher.exams.store'), [
                'name' => 'Brand New Pop Quiz Today',
                'assignment' => $this->classSubject->id,
                'exam_date' => '2026-02-01',
                'max_marks' => 20,
            ]);

        $response = $this->actingAs($this->teacherUser)->get(route('teacher.exams.index'));
        $response->assertOk();

        // The newly created exam MUST appear first before the future exam in the list HTML
        $content = $response->getContent();
        $posNew = strpos($content, 'Brand New Pop Quiz Today');
        $posFuture = strpos($content, 'Future End-Year Physics Finals');

        $this->assertNotFalse($posNew, 'Newly created exam not found in list.');
        $this->assertNotFalse($posFuture, 'Existing exam not found in list.');
        $this->assertLessThan($posFuture, $posNew, 'Newly created exam must appear at the very top of the list.');
    }
}
