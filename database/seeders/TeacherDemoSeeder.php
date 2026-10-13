<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\Term;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Database\Seeder;

/**
 * Needs AdminDemoSeeder first. Creates 5 days of attendance for every class
 * subject, one published "Mid Term Exam" with marks, and one draft "Quiz 1".
 */
class TeacherDemoSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::current();
        if (! $term) {
            return;
        }

        mt_srand(42); // same random data every time

        $classSubjects = ClassSubject::with('section.students')->where('term_id', $term->id)->get();
        $teacherUser = User::where('email', 'teacher@school.test')->first();

        // ---- attendance: last 5 days ----
        foreach ($classSubjects as $cs) {
            for ($d = 1; $d <= 5; $d++) {
                $session = AttendanceSession::firstOrCreate(
                    ['class_subject_id' => $cs->id, 'session_date' => now()->subDays($d)->toDateString()],
                    ['taken_by' => $cs->staff?->user_id, 'status' => 'submitted']
                );

                foreach ($cs->section->students as $student) {
                    $roll = mt_rand(1, 100);
                    $status = $roll <= 82 ? 'present' : ($roll <= 90 ? 'late' : ($roll <= 97 ? 'absent' : 'excused'));

                    AttendanceRecord::firstOrCreate(
                        ['attendance_session_id' => $session->id, 'student_id' => $student->id],
                        ['status' => $status]
                    );
                }
            }
        }

        // ---- published exam with marks ----
        $exam = Exam::firstOrCreate(
            ['term_id' => $term->id, 'name' => 'Mid Term Exam'],
            ['type' => 'midterm', 'status' => 'draft', 'created_by' => $teacherUser?->id]
        );

        foreach ($classSubjects as $cs) {
            $es = ExamSubject::firstOrCreate(
                ['exam_id' => $exam->id, 'class_subject_id' => $cs->id],
                ['max_marks' => 100, 'pass_marks' => 40, 'exam_date' => now()->subDays(7)->toDateString()]
            );

            foreach ($cs->section->students as $student) {
                $absent = mt_rand(1, 100) <= 4;
                Mark::firstOrCreate(
                    ['exam_subject_id' => $es->id, 'student_id' => $student->id],
                    [
                        'marks_obtained' => $absent ? null : mt_rand(28, 98),
                        'is_absent' => $absent,
                        'entered_by' => $cs->staff?->user_id,
                    ]
                );
            }
        }

        // Publishing is done by Rajeeya's GradingService. Skipped if it is not merged yet.
        if (! $exam->isPublished() && class_exists(GradingService::class)) {
            app(GradingService::class)->publish($exam);
        }

        // ---- a draft exam to show the "create exam" flow ----
        $quiz = Exam::firstOrCreate(
            ['term_id' => $term->id, 'name' => 'Quiz 1'],
            ['type' => 'quiz', 'status' => 'draft', 'created_by' => $teacherUser?->id]
        );
        $first = $classSubjects->firstWhere('staff_id', $teacherUser?->staff?->id);
        if ($first) {
            ExamSubject::firstOrCreate(
                ['exam_id' => $quiz->id, 'class_subject_id' => $first->id],
                ['max_marks' => 20, 'pass_marks' => 8]
            );
        }
    }
}
