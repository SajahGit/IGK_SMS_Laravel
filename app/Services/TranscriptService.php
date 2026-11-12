<?php

namespace App\Services;

use App\Models\Student;
use App\Models\SubjectResult;
use App\Models\TermResult;

class TranscriptService
{
    public static function forStudent(Student $student): array
    {
        $results = TermResult::query()
            ->with(['term.academicYear', 'schoolClass'])
            ->where('student_id', $student->id)
            ->where('publication_status', 'published')
            ->orderBy('term_id')
            ->get()
            ->map(function (TermResult $result) {
                $subjects = SubjectResult::query()
                    ->with('subject')
                    ->where('term_id', $result->term_id)
                    ->where('class_id', $result->class_id)
                    ->where('student_id', $result->student_id)
                    ->where('result_status', 'graded')
                    ->orderBy('subject_id')
                    ->get();
                $credits = (int) $subjects->sum('credit_hours');
                $weightedPoints = $subjects->sum(fn (SubjectResult $subject) => (float) $subject->grade_point * (int) $subject->credit_hours);

                return [
                    'term_result' => $result,
                    'term' => $result->term,
                    'academic_year' => $result->term?->academicYear,
                    'subjects' => $subjects,
                    'credits' => $credits,
                    'weighted_points' => $weightedPoints,
                    'gpa' => $credits > 0 ? round($weightedPoints / $credits, 2, PHP_ROUND_HALF_UP) : null,
                ];
            });

        $years = $results->groupBy(fn (array $row) => $row['academic_year']?->id ?? 0)
            ->map(function ($terms) {
                $credits = (int) $terms->sum('credits');
                $weightedPoints = $terms->sum('weighted_points');
                $first = $terms->first();

                return [
                    'academic_year' => $first['academic_year'],
                    'terms' => $terms->values(),
                    'credits' => $credits,
                    'gpa' => $credits > 0 ? round($weightedPoints / $credits, 2, PHP_ROUND_HALF_UP) : null,
                ];
            })
            ->values();

        $totalCredits = (int) $results->sum('credits');
        $totalWeightedPoints = $results->sum('weighted_points');

        return [
            'student' => $student->load(['user', 'class']),
            'years' => $years,
            'totalCredits' => $totalCredits,
            'cgpa' => $totalCredits > 0 ? round($totalWeightedPoints / $totalCredits, 2, PHP_ROUND_HALF_UP) : null,
        ];
    }
}
