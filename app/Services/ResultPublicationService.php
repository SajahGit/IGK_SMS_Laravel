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

        if ($exam->term_id) {
            $latestTermResult = TermResult::query()
                ->where('term_id', $exam->term_id)
                ->where('class_id', $exam->class_id)
                ->whereIn('publication_status', ['pending_approval', 'published'])
                ->latest('generated_at')
                ->first();

            if ($latestTermResult) {
                // An exam is fresh/unlocked if:
                // 1. It has never had any recorded marks, OR
                // 2. It was created after the term result was generated, OR
                // 3. It was never published (is_published=false and published_at=null) and not yet included in published term results
                $hasRecordedMarks = $exam->results()->where(function ($q) {
                    $q->whereNotNull('marks')->orWhere('is_absent', true)->orWhere('is_excused', true);
                })->exists();

                $isFreshExam = ! $hasRecordedMarks
                    || ($exam->created_at && $latestTermResult->generated_at && $exam->created_at->gt($latestTermResult->generated_at))
                    || (! $exam->is_published && is_null($exam->published_at) && $exam->created_at && $latestTermResult->generated_at && $exam->created_at->gte($latestTermResult->generated_at));

                if (! $isFreshExam) {
                    abort(403, 'Term results are submitted or published and marks are locked.');
                }
            }
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
