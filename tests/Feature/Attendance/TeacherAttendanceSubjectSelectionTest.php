<?php

namespace Tests\Feature\Attendance;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAttendanceSubjectSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_attendance_defaults_to_an_assigned_subject_and_saves_attendance(): void
    {
        $previousAcademicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'is_current' => false,
        ]);
        $previousTerm = Term::create([
            'academic_year_id' => $previousAcademicYear->id,
            'name' => 'Previous Term',
            'sequence' => 1,
            'start_date' => '2025-09-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $schoolClass = SchoolClass::create(['name' => 'Grade 11', 'section' => 'D']);
        $previousYearClass = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $teacherUser = User::create([
            'name' => 'Mr. Lathif',
            'email' => 'lathif@school.test',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'status' => 'active',
        ]);

        $mathematics = Subject::create(['name' => 'Mathematics', 'code' => 'MTH']);
        $calculus = Subject::create(['name' => 'AP Calculus', 'code' => 'APC']);
        $otherSubject = Subject::create(['name' => 'Physics', 'code' => 'PHY']);
        $previousTermSubject = Subject::create(['name' => 'Previous Year Subject', 'code' => 'PYS']);
        $mathematicsOffering = ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $previousTermSubject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $previousTerm->id,
        ]);
        $previousYearOffering = ClassSubject::create([
            'class_id' => $previousYearClass->id,
            'subject_id' => $previousTermSubject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $previousTerm->id,
        ]);
        ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $calculus->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
        ]);
        $otherTeacherUser = User::create([
            'name' => 'Another Teacher',
            'email' => 'another@school.test',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'status' => 'active',
        ]);
        ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $otherTeacher->id,
            'term_id' => $term->id,
        ]);

        $studentUser = User::create([
            'name' => 'Student One',
            'email' => 'student@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $schoolClass->id,
            'admission_no' => 'STU001',
            'status' => 'active',
        ]);
        $mathematicsOffering->students()->attach($student->id, ['status' => 'enrolled']);

        $this->actingAs($teacherUser)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('AP Calculus')
            ->assertSee('Previous Year Subject');

        $this->actingAs($teacherUser)
            ->get(route('teacher.attendance.create', ['class_id' => $schoolClass->id]))
            ->assertOk()
            ->assertSee('Select Subject')
            ->assertSee('Mathematics')
            ->assertSee('AP Calculus')
            ->assertDontSee('Physics')
            ->assertDontSee('Previous Year Subject')
            ->assertSee('value="'.$mathematicsOffering->id.'" class="bg-slate-800 text-white" selected', false);

        $this->actingAs($teacherUser)
            ->get(route('teacher.attendance.create'))
            ->assertOk()
            ->assertSee('Grade 11 - D')
            ->assertSee('Grade 10 - A');

        $previousYearStudentUser = User::create([
            'name' => 'Previous Year Student',
            'email' => 'previous-student@school.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        $previousYearStudent = Student::create([
            'user_id' => $previousYearStudentUser->id,
            'class_id' => $previousYearClass->id,
            'admission_no' => 'STU002',
            'status' => 'active',
        ]);
        $previousYearOffering->students()->attach($previousYearStudent->id, ['status' => 'enrolled']);

        $this->actingAs($teacherUser)
            ->get(route('teacher.attendance.create', ['class_id' => $previousYearClass->id]))
            ->assertOk()
            ->assertSee('Previous Year Subject')
            ->assertSee('value="'.$previousYearOffering->id.'" class="bg-slate-800 text-white" selected', false);

        $this->actingAs($teacherUser)
            ->post(route('teacher.attendance.store'), [
                'class_id' => $previousYearClass->id,
                'class_subject_id' => $previousYearOffering->id,
                'date' => today()->toDateString(),
                'start_time' => '09:30',
                'status' => [$previousYearStudent->id => 'present'],
            ])
            ->assertRedirect();

        $payload = [
            'class_id' => $schoolClass->id,
            'date' => today()->toDateString(),
            'start_time' => '08:30',
            'status' => [$student->id => 'present'],
        ];

        $this->actingAs($teacherUser)
            ->post(route('teacher.attendance.store'), $payload)
            ->assertSessionHasErrors([
                'class_subject_id' => 'Please select a subject you teach before recording attendance.',
            ]);

        $this->actingAs($teacherUser)
            ->post(route('teacher.attendance.store'), $payload + [
                'class_subject_id' => $mathematicsOffering->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'class_id' => $schoolClass->id,
            'status' => 'present',
        ]);
    }
}
