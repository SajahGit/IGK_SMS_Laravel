<?php

namespace Database\Seeders;

use App\Models\ClassSubject;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsLesson;
use App\Models\LmsSubmission;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $courseTitle = 'Biology: Cell Structure and Scientific Inquiry';
        $offering = ClassSubject::query()
            ->whereHas('subject', fn ($query) => $query->where('code', 'BIO'))
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('name', 'Grade 10')
                ->whereDoesntHave('students.user', fn ($users) => $users
                    ->where('email', 'not like', 'demo.student.%@school.test')
                    ->where('email', '!=', 'student@school.test')))
            ->whereHas('schoolClass.students.user', fn ($query) => $query->where(function ($emails) {
                $emails->where('email', 'like', 'demo.student.%@school.test')
                    ->orWhere('email', 'student@school.test');
            }))
            ->whereHas('teacher.user')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('student_subject_enrollments as enrollments')
                    ->join('students', 'students.id', '=', 'enrollments.student_id')
                    ->join('users', 'users.id', '=', 'students.user_id')
                    ->whereColumn('enrollments.class_subject_id', 'class_subject.id')
                    ->where('enrollments.status', 'enrolled')
                    ->where(fn ($users) => $users
                        ->where('users.email', 'like', 'demo.student.%@school.test')
                        ->orWhere('users.email', 'student@school.test'));
            })
            ->where(function ($query) use ($courseTitle) {
                $query->whereDoesntHave('learningCourse')
                    ->orWhereHas('learningCourse', fn ($courses) => $courses->where('title', $courseTitle));
            })
            ->orderBy('id')
            ->first();

        if (! $offering) {
            throw new RuntimeException('LMS demo data requires a demo-only Grade 10 biology class with an assigned teacher.');
        }

        $course = $offering->learningCourse()->first();
        if (! $course) {
            $course = LmsCourse::create([
                'class_subject_id' => $offering->id,
                'title' => $courseTitle,
                'description' => 'Cambridge-aligned learning materials on cell structure, microscopy, and scientific comparison.',
            ]);
        }

        LmsLesson::firstOrCreate(
            ['course_id' => $course->id, 'position' => 1],
            [
                'title' => 'Cell structure and microscopy',
                'content' => 'Compare plant and animal cells. Identify the nucleus, cytoplasm, cell membrane, cell wall, chloroplasts, and vacuole where present. Explain how a light microscope supports careful observation, and record observations with labels, magnification, and a scale estimate.',
                'published_at' => now(),
            ]
        );

        $assignment = LmsAssignment::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Comparative cell investigation'],
            [
                'instructions' => 'Create a labelled comparison of plant and animal cells. Explain at least three structural differences and how each observation could be checked using a microscope. Include a short conclusion supported by evidence.',
                'due_at' => now()->addDays(30),
                'max_points' => 20,
                'published_at' => now(),
            ]
        );

        $studentId = DB::table('student_subject_enrollments')
            ->join('students', 'students.id', '=', 'student_subject_enrollments.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('student_subject_enrollments.class_subject_id', $offering->id)
            ->where('student_subject_enrollments.status', 'enrolled')
            ->where(fn ($query) => $query
                ->where('users.email', 'like', 'demo.student.%@school.test')
                ->orWhere('users.email', 'student@school.test'))
            ->orderBy('students.id')
            ->value('students.id');

        if (! $studentId) {
            throw new RuntimeException('LMS demo data requires an enrolled demo student in the selected biology class.');
        }

        $student = Student::query()->findOrFail($studentId);
        $teacher = Teacher::query()->findOrFail($offering->teacher_id);
        LmsSubmission::firstOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'body' => 'Plant cells have a cell wall, chloroplasts, and a large permanent vacuole. Animal cells do not have a cell wall or chloroplasts. A labelled microscope drawing with magnification recorded would provide evidence for the observed structures.',
                'submitted_at' => now(),
                'score' => 18,
                'feedback' => 'Clear comparison supported by appropriate observations. Add a scale estimate to strengthen the evidence.',
                'graded_at' => now(),
                'graded_by' => $teacher->user_id,
            ]
        );

    }
}
