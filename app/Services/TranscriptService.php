<?php

namespace App\Services;

use App\Models\GradeScale;
use App\Models\Result;
use App\Models\Student;
use App\Models\SubjectResult;
use App\Models\TermResult;

class TranscriptService
{
    public static function forStudent(Student $student): array
    {
        $student->load(['program.gradeScale.items', 'class.program.gradeScale.items']);
        $gradeScale = $student->program?->gradeScale ?? $student->class?->program?->gradeScale;

        $publishedTermResults = TermResult::query()
            ->with(['term.academicYear', 'schoolClass'])
            ->where('student_id', $student->id)
            ->where('publication_status', 'published')
            ->orderBy('term_id')
            ->get();

        $results = $publishedTermResults
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
                    'exam_results' => collect(),
                ];
            })
            ->keyBy(fn (array $row) => $row['term']->id);

        $publishedExamResults = Result::query()
            ->with(['exam.subject', 'exam.term.academicYear'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($query) => $query
                ->where('is_published', true)
                ->whereNotNull('term_id'))
            ->get()
            ->filter(fn (Result $result) => $result->exam?->term);

        $examResultsByTerm = $publishedExamResults
            ->sortBy(fn (Result $result) => $result->exam->exam_date?->getTimestamp() ?? 0)
            ->groupBy(fn (Result $result) => $result->exam->term_id);

        foreach ($examResultsByTerm as $termId => $termResults) {
            $examResults = $termResults->map(function (Result $result) use ($gradeScale) {
                $exam = $result->exam;
                $percentage = $result->is_absent
                    ? ($result->is_excused ? null : 0.0)
                    : GradingService::percentage($result->marks, (int) $exam->max_marks);

                return [
                    'exam' => $exam,
                    'subject' => $exam->subject,
                    'marks' => $result->is_absent ? null : (float) $result->marks,
                    'max_marks' => (int) $exam->max_marks,
                    'status' => $result->is_absent
                        ? ($result->is_excused ? 'Excused' : 'Absent')
                        : null,
                    'percentage' => $percentage,
                    'grade' => $percentage === null ? null : GradingService::grade($percentage, $gradeScale),
                ];
            })->values();

            if ($results->has((int) $termId)) {
                $row = $results->get((int) $termId);
                $row['exam_results'] = $examResults;
                $results->put((int) $termId, $row);

                continue;
            }

            $subjects = $termResults
                ->groupBy(fn (Result $result) => $result->exam->subject_id)
                ->map(function ($subjectResults) use ($gradeScale) {
                    $exam = $subjectResults->first()->exam;
                    $subject = $exam->subject;
                    $percentage = GradingService::calculateSubjectPercentage($subjectResults);

                    if ($percentage === null) {
                        return null;
                    }

                    $grade = GradingService::grade($percentage, $gradeScale);
                    $gradePoint = GradingService::gradePoint($percentage, $gradeScale);

                    return (new SubjectResult([
                        'percentage' => $percentage,
                        'grade' => $grade,
                        'grade_point' => $gradePoint,
                        'credit_hours' => $subject->credit_hours ?? 3,
                        'result_status' => 'graded',
                    ]))->setRelation('subject', $subject);
                })
                ->filter()
                ->sortBy(fn (SubjectResult $subjectResult) => $subjectResult->subject->name)
                ->values();

            $credits = (int) $subjects->sum('credit_hours');
            $weightedPoints = $subjects->sum(fn (SubjectResult $subjectResult) => (
                (float) $subjectResult->grade_point * (int) $subjectResult->credit_hours
            ));
            $resultStatus = $subjects->isEmpty()
                ? 'excused'
                : ($subjects->every(fn (SubjectResult $subjectResult) => GradingService::isPassing(
                    (float) $subjectResult->percentage,
                    $gradeScale
                )) ? 'pass' : 'fail');
            $term = $termResults->first()->exam->term;

            $results->put((int) $termId, [
                'term_result' => new TermResult(['result_status' => $resultStatus]),
                'term' => $term,
                'academic_year' => $term->academicYear,
                'subjects' => $subjects,
                'credits' => $credits,
                'weighted_points' => $weightedPoints,
                'gpa' => $credits > 0 ? round($weightedPoints / $credits, 2, PHP_ROUND_HALF_UP) : null,
                'exam_results' => $examResults,
            ]);
        }

        $results = $results
            ->sortBy(fn (array $row) => $row['term']->sequence ?? $row['term']->id)
            ->values();

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
            'gradePointMax' => (float) (GradeScaleService::items($gradeScale ?? GradeScale::where('is_default', true)->first())->max('grade_point') ?? 4),
        ];
    }
}
