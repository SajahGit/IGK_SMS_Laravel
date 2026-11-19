<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableSlot;
use Database\Seeders\AcademicStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public static function lifecycleDates(): array
    {
        return [
            'autumn term' => ['2026-11-19', '2026-2027', 'Term 1'],
            'spring term opening' => ['2027-01-18', '2026-2027', 'Term 2'],
            'summer term opening' => ['2027-04-26', '2026-2027', 'Term 3'],
            'new academic year' => ['2027-09-01', '2027-2028', 'Term 1'],
        ];
    }

    #[DataProvider('lifecycleDates')]
    public function test_it_activates_the_year_and_term_matching_the_simulated_date(
        string $date,
        string $yearName,
        string $termName
    ): void {
        $this->artisan('school:sync-lifecycle', ['--simulate-date' => $date])
            ->assertExitCode(0);

        $this->assertDatabaseHas('academic_years', [
            'name' => $yearName,
            'is_current' => true,
            'status' => 'active',
        ]);
        $this->assertTrue(DB::table('academic_years')
            ->where('name', $yearName)
            ->whereDate('start_date', $yearName === '2026-2027' ? '2026-09-01' : '2027-09-01')
            ->whereDate('end_date', $yearName === '2026-2027' ? '2027-07-16' : '2028-07-16')
            ->exists());
        $this->assertDatabaseHas('terms', [
            'name' => $termName,
            'is_current' => true,
            'status' => 'active',
        ]);
        $this->assertSame(3, DB::table('terms')
            ->where('academic_year_id', DB::table('academic_years')->where('name', '2026-2027')->value('id'))
            ->count());
        $this->assertSame(1, DB::table('academic_years')->where('is_current', true)->count());
        $this->assertSame(1, DB::table('terms')->where('is_current', true)->count());
    }

    public function test_transitions_carry_forward_enrollments_timetables_and_unlocked_assessments_idempotently(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        $this->artisan('school:sync-lifecycle', ['--simulate-date' => '2026-11-19'])
            ->assertExitCode(0);

        $year = AcademicYear::query()->where('name', '2026-2027')->firstOrFail();
        $termOne = Term::query()->where('academic_year_id', $year->id)->where('sequence', 1)->firstOrFail();
        $ibMyp = DB::table('programs')->where('code', 'IBMYP')->value('id');
        $math = DB::table('subjects')->where('code', 'MTH')->value('id');
        $teacherUserId = DB::table('users')->insertGetId([
            'name' => 'Lifecycle Teacher',
            'email' => 'lifecycle-teacher@example.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teacher = Teacher::query()->create([
            'user_id' => $teacherUserId,
            'status' => 'active',
        ]);

        $gradeNineId = DB::table('classes')->insertGetId([
            'name' => 'Grade 9',
            'section' => 'A',
            'capacity' => 40,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gradeTenId = DB::table('classes')->insertGetId([
            'name' => 'Grade 10',
            'section' => 'A',
            'capacity' => 40,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('classes')->insert([
            'name' => 'Grade 11',
            'section' => 'A',
            'capacity' => 40,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finalGradeId = DB::table('classes')->insertGetId([
            'name' => 'Grade 12',
            'section' => 'A',
            'capacity' => 40,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentUserId = DB::table('users')->insertGetId([
            'name' => 'Lifecycle Student',
            'email' => 'lifecycle-student@example.test',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $studentUserId,
            'class_id' => $gradeNineId,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'admission_no' => 'LIFECYCLE-001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finalStudentUserId = DB::table('users')->insertGetId([
            'name' => 'Final Year Student',
            'email' => 'final-year-student@example.test',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finalStudentId = DB::table('students')->insertGetId([
            'user_id' => $finalStudentUserId,
            'class_id' => $finalGradeId,
            'program_id' => $ibMyp,
            'academic_year_id' => $year->id,
            'admission_no' => 'LIFECYCLE-002',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sourceOffering = ClassSubject::query()->create([
            'class_id' => $gradeNineId,
            'subject_id' => $math,
            'teacher_id' => $teacher->id,
            'term_id' => $termOne->id,
        ]);
        DB::table('student_subject_enrollments')->insert([
            'student_id' => $studentId,
            'class_subject_id' => $sourceOffering->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        TimetableSlot::query()->create([
            'class_subject_id' => $sourceOffering->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:40:00',
            'room' => 'Lifecycle Room',
        ]);

        foreach (['2027-01-18', '2027-04-26', '2027-09-01'] as $date) {
            $this->artisan('school:sync-lifecycle', ['--simulate-date' => $date])
                ->assertExitCode(0);
        }

        $newYear = AcademicYear::query()->where('name', '2027-2028')->firstOrFail();
        $newTerm = Term::query()->where('academic_year_id', $newYear->id)->where('sequence', 1)->firstOrFail();
        $promotedStudent = Student::withoutGlobalScopes()->findOrFail($studentId);
        $this->assertSame($gradeTenId, $promotedStudent->class_id);
        $this->assertSame($newYear->id, $promotedStudent->academic_year_id);
        $this->assertSame('graduated', Student::withoutGlobalScopes()->findOrFail($finalStudentId)->status);

        $newOffering = ClassSubject::query()
            ->where('class_id', $gradeTenId)
            ->where('subject_id', $math)
            ->where('term_id', $newTerm->id)
            ->firstOrFail();
        $this->assertDatabaseHas('student_subject_enrollments', [
            'student_id' => $studentId,
            'class_subject_id' => $newOffering->id,
            'status' => 'enrolled',
        ]);
        $this->assertDatabaseHas('timetable_slots', [
            'class_subject_id' => $newOffering->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:40:00',
        ]);
        $this->assertDatabaseHas('exams', [
            'class_id' => $gradeTenId,
            'subject_id' => $math,
            'term_id' => $newTerm->id,
            'status' => 'scheduled',
            'is_published' => false,
        ]);
        $this->assertDatabaseHas('announcements', [
            'title' => '2026-2027 Spring Break',
            'published_at' => '2027-01-18 00:00:00',
        ]);
        $this->assertDatabaseHas('announcements', [
            'title' => '2026-2027 Board Exams',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic_lifecycle_academic_year_transition',
        ]);

        $this->artisan('school:sync-lifecycle', ['--simulate-date' => '2027-09-01'])
            ->assertExitCode(0);
        $this->assertSame(1, ClassSubject::query()
            ->where('class_id', $gradeTenId)
            ->where('subject_id', $math)
            ->where('term_id', $newTerm->id)
            ->count());
    }

    public function test_simulated_date_is_rejected_outside_local_and_testing_environments(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('school:sync-lifecycle', ['--simulate-date' => '2027-01-18'])
            ->assertExitCode(1);

        $this->assertSame(0, DB::table('academic_years')->count());
    }

    public function test_invalid_simulated_date_is_rejected_without_writing_calendar_data(): void
    {
        $this->artisan('school:sync-lifecycle', ['--simulate-date' => '2027-02-30'])
            ->assertExitCode(1);

        $this->assertSame(0, DB::table('academic_years')->count());
    }
}
