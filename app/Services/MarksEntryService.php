<?php

namespace App\Services;

use App\Exceptions\MarksLockedException;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarksEntryService
{
    public static function enrolledStudentIds(Exam $exam): Collection
    {
        $offeringQuery = ClassSubject::query()
            ->where('class_id', $exam->class_id)
            ->where('subject_id', $exam->subject_id);
        $offering = $exam->term_id
            ? (clone $offeringQuery)->where('term_id', $exam->term_id)->first()
                ?? (clone $offeringQuery)->whereNull('term_id')->first()
            : $offeringQuery->whereNull('term_id')->first();

        if (! $offering) {
            return Student::where('class_id', $exam->class_id)
                ->where('status', 'active')
                ->orderBy('admission_no')
                ->pluck('id');
        }

        $enrolledStudentIds = DB::table('student_subject_enrollments')
            ->where('class_subject_id', $offering->id)
            ->where('status', 'enrolled')
            ->pluck('student_id');

        return Student::whereIn('id', $enrolledStudentIds)
            ->where('status', 'active')
            ->orderBy('admission_no')
            ->pluck('id');
    }

    /**
     * Bulk-save marks for one exam inside a single transaction.
     *
     * Upserts on the unique (exam_id, student_id) key, rejects changes when the
     * exam results are published (MarksLockedException), and audits every change.
     * Only students enrolled in the exam's class may be marked.
     *
     * @param  array  $marks  keyed by student_id => marks (nullable)
     * @param  array  $absent  keyed by student_id => boolean
     * @param  array  $excused  keyed by student_id => boolean
     *
     * @throws MarksLockedException
     * @throws ValidationException
     */
    public static function bulkSave(Exam $exam, array $marks, array $absent, array $excused = []): void
    {
        if ($exam->is_published) {
            throw new MarksLockedException;
        }

        $studentIds = self::enrolledStudentIds($exam);

        DB::transaction(function () use ($exam, $studentIds, $marks, $absent, $excused) {
            foreach ($studentIds as $studentId) {
                $isAbsent = ! empty($absent[$studentId]);
                $isExcused = ! empty($excused[$studentId]);
                $mark = $marks[$studentId] ?? null;

                if ($isExcused && ! $isAbsent) {
                    throw ValidationException::withMessages([
                        "is_excused.{$studentId}" => 'An excused result must also be marked absent.',
                    ]);
                }

                if ($isAbsent) {
                    $result = Result::updateOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $studentId],
                        ['marks' => null, 'is_absent' => true, 'is_excused' => $isExcused]
                    );
                } elseif ($mark !== null && $mark !== '') {
                    $mark = (float) $mark;
                    $result = Result::updateOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $studentId],
                        ['marks' => $mark, 'is_absent' => false, 'is_excused' => false]
                    );
                } else {
                    $existingResult = Result::where('exam_id', $exam->id)
                        ->where('student_id', $studentId)
                        ->first();

                    if ($existingResult) {
                        $oldValues = $existingResult->only(['marks', 'is_absent']);
                        $existingResult->delete();
                        AuditLogger::log('mark_deleted', $existingResult, $oldValues, null);
                    }

                    continue;
                }

                AuditLogger::log('marks_updated', $result, null, [
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'marks' => $result->marks,
                    'is_absent' => $result->is_absent,
                    'is_excused' => $result->is_excused,
                ]);
            }
        });
    }
}
