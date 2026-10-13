<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opens every main page for every role with the demo data and runs the
 * main flow once:  php artisan test
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin.dashboard', 'teacher.dashboard', 'student.dashboard', 'parent.dashboard', 'reports.index'] as $needed) {
            if (! \Illuminate\Support\Facades\Route::has($needed)) {
                $this->markTestSkipped('Run this test after all four modules are pasted in.');
            }
        }
        $this->withoutVite();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_admin_pages_open(): void
    {
        $admin = $this->user('admin@school.test');

        foreach ([
            'admin.dashboard', 'admin.users.index', 'admin.academic-years.index', 'admin.academic-years.create',
            'admin.terms.index', 'admin.terms.create', 'admin.classes.index', 'admin.sections.index',
            'admin.subjects.index', 'admin.subjects.create', 'admin.teachers.index', 'admin.teachers.create',
            'admin.students.index', 'admin.students.create', 'admin.guardians.index', 'admin.guardians.create',
            'admin.class-subjects.index', 'reports.index', 'reports.students', 'reports.attendance',
            'reports.marksheet', 'reports.class-result',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('reports.students', ['export' => 'csv']))->assertOk();
        $this->actingAs($admin)->get(route('reports.attendance', ['export' => 'csv']))->assertOk();
    }

    public function test_teacher_pages_open_and_attendance_can_be_saved(): void
    {
        $teacher = $this->user('teacher@school.test');
        $cs = ClassSubject::with('section.students')->where('staff_id', $teacher->staff->id)->firstOrFail();

        foreach (['teacher.dashboard', 'teacher.my-classes.index', 'teacher.attendance.index',
            'teacher.attendance.create', 'teacher.exams.index', 'teacher.exams.create',
            'reports.index', 'reports.students'] as $route) {
            $this->actingAs($teacher)->get(route($route))->assertOk();
        }
        $this->actingAs($teacher)->get(route('teacher.my-classes.show', $cs))->assertOk();

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'class_subject_id' => $cs->id,
            'session_date' => today()->toDateString(),
        ])->assertRedirect();

        $session = AttendanceSession::latest('id')->firstOrFail();
        $this->actingAs($teacher)->get(route('teacher.attendance.show', $session))->assertOk();

        $statuses = $cs->section->students->mapWithKeys(fn ($s) => [$s->id => 'present'])->all();
        $this->actingAs($teacher)
            ->put(route('teacher.attendance.records.update', $session), ['status' => $statuses])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['attendance_session_id' => $session->id, 'status' => 'present']);
    }

    public function test_exam_flow_from_marks_to_published_result(): void
    {
        $teacher = $this->user('teacher@school.test');
        $cs = ClassSubject::with('section.students')->where('staff_id', $teacher->staff->id)->firstOrFail();
        $students = $cs->section->students;
        $first = $students->first();

        // create exam + add subject
        $this->actingAs($teacher)->post(route('teacher.exams.store'), ['name' => 'Final Test', 'type' => 'final'])->assertRedirect();
        $exam = Exam::where('name', 'Final Test')->firstOrFail();

        $this->actingAs($teacher)->post(route('teacher.exams.subjects.store', $exam), [
            'class_subject_id' => $cs->id, 'max_marks' => 100, 'pass_marks' => 40,
        ])->assertSessionHasNoErrors();
        $examSubject = $exam->examSubjects()->firstOrFail();

        $this->actingAs($teacher)->get(route('teacher.exams.show', $exam))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.marks.show', $examSubject))->assertOk();

        // invalid mark is rejected
        $this->actingAs($teacher)->post(route('teacher.marks.store', $examSubject), [
            'marks' => [$first->id => 150],
        ])->assertSessionHasErrors();

        // valid marks, one student absent
        $marks = $students->mapWithKeys(fn ($s) => [$s->id => 80])->all();
        $absent = [$students->last()->id => 1];
        $this->actingAs($teacher)->post(route('teacher.marks.store', $examSubject), ['marks' => $marks, 'absent' => $absent])
            ->assertSessionHasNoErrors();

        // publish
        $this->actingAs($teacher)->post(route('teacher.exams.publish', $exam))->assertSessionHas('success');
        $this->assertDatabaseHas('exam_results', ['exam_id' => $exam->id, 'student_id' => $first->id]);
        $this->assertDatabaseHas('subject_results', ['exam_id' => $exam->id, 'student_id' => $first->id, 'grade' => 'A']);

        // marks are locked now
        $this->actingAs($teacher)->post(route('teacher.marks.store', $examSubject), ['marks' => $marks])->assertForbidden();

        // student sees the result
        $this->actingAs($first->user)->get(route('student.results.index'))->assertOk()->assertSee('Final Test');
    }

    public function test_student_pages_open_and_other_students_are_protected(): void
    {
        $student = $this->user('student@school.test');

        foreach (['student.dashboard', 'student.profile.show', 'student.attendance.index', 'student.results.index'] as $route) {
            $this->actingAs($student)->get(route($route))->assertOk();
        }

        $me = $student->student;
        $this->actingAs($student)->get(route('reports.card', $me))->assertOk();

        $other = Student::where('id', '!=', $me->id)->firstOrFail();
        $this->actingAs($student)->get(route('reports.card', $other))->assertForbidden();
    }

    public function test_parent_sees_only_linked_children(): void
    {
        $parent = $this->user('parent@school.test');
        $child = $parent->guardian->students()->firstOrFail();
        $stranger = Student::whereNotIn('id', $parent->guardian->students()->pluck('students.id'))->firstOrFail();

        $this->actingAs($parent)->get(route('parent.dashboard'))->assertOk();
        $this->actingAs($parent)->get(route('parent.attendance.show', $child))->assertOk();
        $this->actingAs($parent)->get(route('parent.results.show', $child))->assertOk();
        $this->actingAs($parent)->get(route('reports.card', $child))->assertOk();

        $this->actingAs($parent)->get(route('parent.attendance.show', $stranger))->assertForbidden();
        $this->actingAs($parent)->get(route('parent.results.show', $stranger))->assertForbidden();
    }

    public function test_login_page_and_logout(): void
    {
        $this->get(route('login'))->assertOk();
        $this->post(route('login.attempt'), ['email' => 'admin@school.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
    }
}
