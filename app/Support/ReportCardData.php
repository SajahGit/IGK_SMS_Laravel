<?php

namespace App\Support;

use App\Models\Result;
use App\Models\Student;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\GradeScaleService;

class ReportCardData
{
    public static function forStudent(Student $student, ?int $termId = null): array
    {
        $terms = Term::with('academicYear')->orderBy('sequence')->get();
        $selectedTerm = $termId ? Term::find($termId) : Term::where('is_current', true)->first();
        $student->load(['program.gradeScale.items', 'class.program.gradeScale.items']);
        $gradeScale = $student->program?->gradeScale ?? $student->class?->program?->gradeScale;

        $resultsQuery = Result::with(['exam.subject', 'exam.term'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($query) => $query->where('is_published', true));

        if ($selectedTerm) {
            $resultsQuery->whereHas('exam', fn ($query) => $query->where('term_id', $selectedTerm->id));
        }

        $results = $resultsQuery->get();
        $termResult = $selectedTerm
            ? TermResult::query()
                ->where('term_id', $selectedTerm->id)
                ->where('student_id', $student->id)
                ->where('publication_status', 'published')
                ->first()
            : null;
        $subjectResults = $termResult
            ? SubjectResult::with('subject')
                ->where('term_id', $selectedTerm->id)
                ->where('class_id', $termResult->class_id)
                ->where('student_id', $student->id)
                ->orderBy('subject_id')
                ->get()
            : collect();

        return [
            'student' => $student->load(['user', 'class', 'primaryGuardian.user']),
            'results' => $results,
            'termResult' => $termResult,
            'subjectResults' => $subjectResults,
            'gradeItems' => GradeScaleService::items($gradeScale),
            'gradePointMax' => GradeScaleService::items($gradeScale)->max(fn ($item) => (float) $item->grade_point) ?? 4,
            'gradeScale' => $gradeScale,
            'gpa' => $termResult ? $termResult->gpa : SchoolHelper::calculateTermGpa($results, $gradeScale),
            'attendance' => SchoolHelper::attendanceSummary($student->id),
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'institution' => InstitutionDetails::values(),
        ];
    }
}
