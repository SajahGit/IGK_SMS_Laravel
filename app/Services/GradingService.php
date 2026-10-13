<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SubjectResult;
use Illuminate\Support\Facades\DB;

/**
 * Turns the marks of an exam into grades, grade points and GPA.
 * Grade scale lives in config/school.php  ('grade_scale').
 */
class GradingService
{
    /** @return array{0: string, 1: float}  [grade, grade point] */
    public function grade(float $percentage): array
    {
        foreach (config('school.grade_scale') as $band) {
            if ($percentage >= $band['min']) {
                return [$band['grade'], (float) $band['point']];
            }
        }

        return ['F', 0.0];
    }

    /** Calculates every result of the exam and locks it (status = published). */
    public function publish(Exam $exam): void
    {
        DB::transaction(function () use ($exam) {
            $exam->load('examSubjects.marks');
            $perStudent = [];

            foreach ($exam->examSubjects as $examSubject) {
                foreach ($examSubject->marks as $mark) {
                    $absent = $mark->is_absent || $mark->marks_obtained === null;
                    $obtained = $absent ? null : (float) $mark->marks_obtained;
                    $percentage = $absent ? 0.0 : round($obtained / $examSubject->max_marks * 100, 2);
                    $pass = ! $absent && $obtained >= $examSubject->pass_marks;

                    if ($absent) {
                        [$grade, $point] = ['AB', 0.0];
                    } elseif (! $pass) {
                        [$grade, $point] = ['F', 0.0];
                    } else {
                        [$grade, $point] = $this->grade($percentage);
                    }

                    SubjectResult::updateOrCreate(
                        ['exam_subject_id' => $examSubject->id, 'student_id' => $mark->student_id],
                        [
                            'exam_id' => $exam->id,
                            'class_subject_id' => $examSubject->class_subject_id,
                            'marks_obtained' => $obtained,
                            'max_marks' => $examSubject->max_marks,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'grade_point' => $point,
                            'is_pass' => $pass,
                            'is_absent' => $absent,
                        ]
                    );

                    $perStudent[$mark->student_id][] = ['point' => $point, 'pass' => $pass];
                }
            }

            foreach ($perStudent as $studentId => $rows) {
                ExamResult::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $studentId],
                    [
                        'gpa' => round(collect($rows)->avg('point'), 2),
                        'subjects_total' => count($rows),
                        'subjects_passed' => collect($rows)->where('pass', true)->count(),
                        'published_at' => now(),
                    ]
                );
            }

            $exam->update(['status' => 'published', 'published_at' => now()]);
        });
    }
}
