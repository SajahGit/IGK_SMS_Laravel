<?php

namespace Tests\Feature;

use App\Jobs\GenerateTermResults;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultGeneration;
use App\Models\User;
use App\Notifications\TermResultsPublished;
use App\Services\GradeScaleService;
use App\Services\ResultPublicationService;
use App\Services\TermResultService;
use App\Support\ReportCardData;
use Database\Seeders\GradeScaleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TermResultsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GradeScaleService::clearCache();
    }

    public function test_term_results_are_idempotent_publishable_and_used_by_student_report_card(): void
    {
        $this->seed(GradeScaleSeeder::class);
        $context = $this->createContext();
        $student = $this->enrollStudent($context['offering'], 'Top Student', 'top@school.test');
        $exam = $this->createExam($context, 'Final', 100, true);
        Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => 85]);

        $service = app(TermResultService::class);
        $first = $service->generate($context['term'], $context['class']->id);
        $second = $service->generate($context['term'], $context['class']->id);

        $this->assertSame([], $first['missing_pairs']);
        $this->assertSame([], $second['missing_pairs']);
        $this->assertSame(1, $first['generated_subject_results']);
        $this->assertSame(1, SubjectResult::count());
        $this->assertSame('7', SubjectResult::first()->grade);
        $this->assertSame(7.0, (float) TermResult::first()->gpa);

        $admin = User::create(['name' => 'Admin', 'email' => 'workflow-admin@school.test', 'password' => 'password', 'role' => 'admin']);
        $service->submitForApproval($context['term'], $context['class']->id, $admin);
        $this->assertSame('pending_approval', TermResult::first()->publication_status);
        $service->publishClass($context['term'], $context['class']->id, $admin);
        $this->assertSame('published', TermResult::first()->publication_status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->user_id,
            'type' => TermResultsPublished::class,
        ]);

        $exam->update(['is_published' => false]);
        try {
            ResultPublicationService::verifyNotLocked($exam->fresh());
            $this->fail('Published term results must lock their exam marks.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $studentUser = $student->user;
        $this->assertNotNull(ReportCardData::forStudent($student)['termResult']);
        $this->actingAs($studentUser)
            ->get(route('student.report-card'))
            ->assertOk()
            ->assertSee('Maths')
            ->assertSee('Weighted term result')
            ->assertSee('4.00');
    }

    public function test_generation_reports_every_missing_pair_and_blocks_submission_until_complete(): void
    {
        $context = $this->createContext();
        $complete = $this->enrollStudent($context['offering'], 'Complete Student', 'complete@school.test');
        $missing = $this->enrollStudent($context['offering'], 'Missing Student', 'missing@school.test');
        $dropped = $this->enrollStudent($context['offering'], 'Dropped Student', 'dropped@school.test', 'dropped');
        $exam = $this->createExam($context, 'Term Exam', 100, true);
        Result::create(['exam_id' => $exam->id, 'student_id' => $complete->id, 'marks' => 70]);

        $service = app(TermResultService::class);
        $summary = $service->generate($context['term'], $context['class']->id);

        $this->assertCount(1, $summary['missing_pairs']);
        $this->assertSame('Missing Student', $summary['missing_pairs'][0]['student']);
        $this->assertSame('Maths', $summary['missing_pairs'][0]['subject']);
        $this->assertSame('incomplete', TermResult::where('student_id', $missing->id)->value('result_status'));
        $this->assertDatabaseMissing('term_results', ['student_id' => $dropped->id]);

        $admin = User::create(['name' => 'Admin', 'email' => 'missing-admin@school.test', 'password' => 'password', 'role' => 'admin']);
        try {
            $service->submitForApproval($context['term'], $context['class']->id, $admin);
            $this->fail('Incomplete term results must not be submitted for approval.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Missing Student / Maths', implode(' ', $exception->errors()['results']));
        }

        Result::create(['exam_id' => $exam->id, 'student_id' => $missing->id, 'marks' => null, 'is_absent' => true]);
        $recalculated = $service->generate($context['term'], $context['class']->id);
        $this->assertSame([], $recalculated['missing_pairs']);
        $this->assertSame(2, TermResult::count());
    }

    public function test_excused_papers_are_excluded_but_unexcused_absence_scores_zero(): void
    {
        $context = $this->createContext();
        $excusedStudent = $this->enrollStudent($context['offering'], 'Excused Student', 'excused@school.test');
        $absentStudent = $this->enrollStudent($context['offering'], 'Absent Student', 'absent@school.test');
        $assessment = $this->createExam($context, 'Assessment', 25, true);
        $final = $this->createExam($context, 'Final', 75, true);
        Result::create(['exam_id' => $assessment->id, 'student_id' => $excusedStudent->id, 'marks' => null, 'is_absent' => true, 'is_excused' => true]);
        Result::create(['exam_id' => $final->id, 'student_id' => $excusedStudent->id, 'marks' => 80]);
        Result::create(['exam_id' => $assessment->id, 'student_id' => $absentStudent->id, 'marks' => null, 'is_absent' => true]);
        Result::create(['exam_id' => $final->id, 'student_id' => $absentStudent->id, 'marks' => null, 'is_absent' => true]);

        app(TermResultService::class)->generate($context['term'], $context['class']->id);

        $excusedSubject = SubjectResult::where('student_id', $excusedStudent->id)->firstOrFail();
        $absentSubject = SubjectResult::where('student_id', $absentStudent->id)->firstOrFail();
        $this->assertSame(80.0, (float) $excusedSubject->percentage);
        $this->assertSame('B', $excusedSubject->grade);
        $this->assertSame(3.0, (float) TermResult::where('student_id', $excusedStudent->id)->value('gpa'));
        $this->assertSame(0.0, (float) $absentSubject->percentage);
        $this->assertSame('F', $absentSubject->grade);
        $this->assertSame(0.0, (float) TermResult::where('student_id', $absentStudent->id)->value('gpa'));
    }

    public function test_grade_ranges_are_validated_and_grade_scale_can_be_changed(): void
    {
        $this->seed(GradeScaleSeeder::class);
        $scale = GradeScale::where('is_default', true)->firstOrFail();
        $items = $scale->items->map(fn ($item) => $item->only([
            'min_percent', 'max_percent', 'grade', 'grade_point', 'is_pass',
        ]))->all();
        $items[1]['min_percent'] = 76;

        try {
            GradeScaleService::validateRanges($items);
            $this->fail('A grade range gap must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $validItems = [
            ['min_percent' => 90, 'max_percent' => 100, 'grade' => 'A', 'grade_point' => 4, 'is_pass' => true],
            ['min_percent' => 80, 'max_percent' => 89.99, 'grade' => 'B', 'grade_point' => 3, 'is_pass' => true],
            ['min_percent' => 0, 'max_percent' => 79.99, 'grade' => 'F', 'grade_point' => 0, 'is_pass' => false],
        ];
        GradeScaleService::updateDefault('Custom Scale', $validItems);
        $this->assertSame('B', GradeScaleService::grade(89.99));
        $this->assertSame('A', GradeScaleService::grade(90));
    }

    public function test_admin_term_results_pages_and_role_scoped_actions_are_available(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $this->artisan('db:seed', ['--class' => GradeScaleSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $admin = User::create(['name' => 'Admin', 'email' => 'screen-admin@school.test', 'password' => 'password', 'role' => 'admin']);
        $admin->syncRoles(['Super Admin']);

        $this->actingAs($admin)
            ->get(route('admin.term-results.index'))
            ->assertOk()
            ->assertSee('Term Results');
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertTrue($admin->can('roles_and_permissions.create'));
        $this->get(route('admin.grade-scale.edit'))
            ->assertOk()
            ->assertSee('Grade Scale');
        $this->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Create Role');
    }

    public function test_large_class_term_result_generation_is_queued(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $context = $this->createContext();
        for ($index = 1; $index <= 201; $index++) {
            $this->enrollStudent(
                $context['offering'],
                "Queued Student {$index}",
                "queued-{$index}@school.test"
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $admin = User::create(['name' => 'Admin', 'email' => 'queue-admin@school.test', 'password' => 'password', 'role' => 'admin']);
        $admin->syncRoles(['Super Admin']);
        Queue::fake();

        $this->actingAs($admin)
            ->post(route('admin.term-results.generate', $context['term']), ['class_id' => $context['class']->id])
            ->assertRedirect(route('admin.term-results.index', [
                'term_id' => $context['term']->id,
                'class_id' => $context['class']->id,
            ]))
            ->assertSessionHas('success');

        Queue::assertPushed(GenerateTermResults::class, fn (GenerateTermResults $job) => (
            $job->termId === $context['term']->id && $job->classId === $context['class']->id
        ));
        $this->assertSame('queued', TermResultGeneration::firstOrFail()->status);
    }

    public function test_term_result_queue_job_records_completion_and_failure(): void
    {
        $context = $this->createContext();
        $generation = TermResultGeneration::create([
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'status' => 'queued',
        ]);

        (new GenerateTermResults($context['term']->id, $context['class']->id))
            ->handle(app(TermResultService::class));
        $this->assertSame('completed', $generation->fresh()->status);

        $classWithoutOfferings = SchoolClass::create(['name' => 'Grade 11', 'section' => 'B']);
        $failedGeneration = TermResultGeneration::create([
            'term_id' => $context['term']->id,
            'class_id' => $classWithoutOfferings->id,
            'status' => 'queued',
        ]);

        try {
            (new GenerateTermResults($context['term']->id, $classWithoutOfferings->id))
                ->handle(app(TermResultService::class));
            $this->fail('Generation without subject offerings must fail.');
        } catch (ValidationException) {
            $this->assertSame('failed', $failedGeneration->fresh()->status);
            $this->assertNotEmpty($failedGeneration->fresh()->error);
        }
    }

    private function createContext(): array
    {
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
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH', 'credit_hours' => 3]);
        $teacherUser = User::create(['name' => 'Teacher', 'email' => 'teacher@school.test', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'phone' => '555']);
        $offering = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);

        return compact('year', 'term', 'class', 'subject', 'teacher', 'offering');
    }

    private function enrollStudent(ClassSubject $offering, string $name, string $email, string $status = 'enrolled'): Student
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'password', 'role' => 'student']);
        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $offering->class_id,
            'admission_no' => strtoupper(substr(md5($email), 0, 10)),
        ]);
        $offering->students()->attach($student->id, [
            'status' => $status,
            'enrolled_at' => now(),
            'dropped_at' => $status === 'dropped' ? now() : null,
        ]);

        return $student;
    }

    private function createExam(array $context, string $name, int $weight, bool $published): Exam
    {
        return Exam::create([
            'name' => $name,
            'class_id' => $context['class']->id,
            'subject_id' => $context['subject']->id,
            'term_id' => $context['term']->id,
            'exam_date' => '2026-05-01',
            'max_marks' => 100,
            'weight' => $weight,
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ]);
    }
}
