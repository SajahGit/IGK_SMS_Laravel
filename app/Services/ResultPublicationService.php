<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Result;
use App\Models\TermResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultPublicationService
{
    /**
     * Prevent modifying marks if the exam results have been published.
     */
    public static function verifyNotLocked(Exam $exam): void
    {
        if ($exam->is_published) {
            abort(403, 'Results are published and marks are locked.');
        }

        if ($exam->term_id && TermResult::query()
            ->where('term_id', $exam->term_id)
            ->where('class_id', $exam->class_id)
            ->whereIn('publication_status', ['pending_approval', 'published'])
            ->exists()) {
            abort(403, 'Term results are submitted or published and marks are locked.');
        }
    }

    /**
     * Publish results after verifying that every student in the class has recorded marks or is absent.
     *
     * @throws ValidationException
     */
    public static function publish(Exam $exam): void
    {
        $classStudents = MarksEntryService::enrolledStudentIds($exam);
        $totalStudents = $classStudents->count();

        if ($totalStudents === 0) {
            throw ValidationException::withMessages([
                'exam' => 'Cannot publish an exam for a class with zero enrolled students.',
            ]);
        }

        $recordedResults = Result::where('exam_id', $exam->id)
            ->whereIn('student_id', $classStudents)
            ->where(function ($q) {
                $q->whereNotNull('marks')->orWhere('is_absent', true);
            })
            ->pluck('student_id');

        if ($recordedResults->count() < $totalStudents) {
            $missingCount = $totalStudents - $recordedResults->count();
            throw ValidationException::withMessages([
                'exam' => "Cannot publish exam: {$missingCount} student(s) still do not have marks recorded or are not marked absent.",
            ]);
        }

        DB::transaction(function () use ($exam) {
            $exam->update([
                'is_published' => true,
                'published_at' => now(),
            ]);

        });
    }

    public static function unpublish(Exam $exam): void
    {
        if ($exam->term_id && TermResult::query()
            ->where('term_id', $exam->term_id)
            ->where('class_id', $exam->class_id)
            ->whereIn('publication_status', ['pending_approval', 'published'])
            ->exists()) {
            abort(403, 'Return term results to draft before unpublishing this exam.');
        }

        DB::transaction(function () use ($exam) {
            $exam->update([
                'is_published' => false,
                'published_at' => null,
            ]);

        });
    }
}
