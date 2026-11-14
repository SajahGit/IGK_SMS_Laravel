<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Program;
use App\Models\Student;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassResultSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $pdfLimit = $request->attributes->get('pdf_export_limit');
        [$classes, $terms, $programs, $classId, $termId, $programId, $subjects, $rows, $stats] = $this->data(
            $request,
            is_int($pdfLimit) ? $pdfLimit : null
        );

        return view('Reports.ReportClassResultSummary', compact(
            'classes', 'terms', 'programs', 'classId', 'termId', 'programId', 'subjects', 'rows', 'stats'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        [, , , , $termId, , $subjects, $rows] = $this->data($request);

        return response()->streamDownload(function () use ($rows, $subjects) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Rank in section', 'Class/Section', 'Admission No', 'Student'], array_map(fn ($subject) => $subject->name, $subjects->all()), ['GPA', 'Pass/Fail']));
            foreach ($rows as $row) {
                $grades = $subjects->map(fn ($subject) => $row['subjects'][$subject->id]['grade'] ?? '—')->all();
                fputcsv($out, array_merge(
                    [$row['rank'] ?? '—', $row['student']->class?->full_name ?? 'Unassigned', $row['student']->admission_no, $row['student']->user->name],
                    $grades,
                    [$row['gpa'] ?? '—', $row['pass'] ? 'Pass' : 'Fail']
                ));
            }
            fclose($out);
        }, "class-result-term-{$termId}.csv", ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request, ?int $pdfLimit = null): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.academic'), 403);
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
        ]);
        $allowedClasses = SchoolHelper::allowedClasses($request->user());
        $classId = $validated['class_id'] ?? null;
        $termId = $validated['term_id'] ?? null;
        $programId = $validated['program_id'] ?? null;
        if ($classId) {
            abort_unless($allowedClasses->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }

        $classes = $allowedClasses->when($programId, fn ($items) => $items->filter(
            fn ($class) => (int) $class->program_id === (int) $programId
        ))->when($classId, fn ($items) => $items->where('id', (int) $classId))->values();
        $terms = Term::orderByDesc('start_date')->get();
        $programs = Program::orderBy('name')->get();
        $rows = collect();
        $stats = null;
        $subjects = collect();

        if ($termId) {
            $offerings = ClassSubject::query()
                ->select(['id', 'class_id', 'subject_id', 'term_id'])
                ->with('subject:id,name,code')
                ->whereIn('class_id', $classes->pluck('id'))
                ->where(fn ($query) => $query->where('term_id', $termId)->orWhereNull('term_id'))
                ->get();
            $subjects = $offerings->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
            $students = Student::query()
                ->select(['id', 'user_id', 'class_id', 'admission_no'])
                ->with(['user:id,name', 'class:id,name,section'])
                ->whereIn('class_id', $classes->pluck('id'))
                ->where('status', 'active')
                ->orderBy('admission_no')
                ->when($pdfLimit !== null, fn ($query) => $query->limit($pdfLimit))
                ->get();
            $allSubjectResults = SubjectResult::query()
                ->select(['id', 'class_id', 'term_id', 'student_id', 'subject_id', 'percentage', 'grade', 'result_status'])
                ->whereIn('class_id', $classes->pluck('id'))
                ->where('term_id', $termId)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('student_id', TermResult::query()
                    ->select('student_id')
                    ->where('term_id', $termId)
                    ->whereIn('class_id', $classes->pluck('id'))
                    ->where('publication_status', 'published'))
                ->get()
                ->groupBy(fn ($result) => $result->student_id);
            $publishedResults = TermResult::query()
                ->select(['id', 'class_id', 'term_id', 'student_id', 'gpa', 'result_status'])
                ->whereIn('class_id', $classes->pluck('id'))
                ->where('term_id', $termId)
                ->where('publication_status', 'published')
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            $rows = $students->map(function ($student) use ($allSubjectResults, $publishedResults, $subjects) {
                $storedSubjects = $allSubjectResults->get($student->id, collect())->keyBy('subject_id');
                $termResult = $publishedResults->get($student->id);
                $subjectGrades = $subjects->mapWithKeys(function ($subject) use ($storedSubjects) {
                    $stored = $storedSubjects->get($subject->id);

                    return [$subject->id => [
                        'percentage' => $stored?->percentage === null ? null : (float) $stored->percentage,
                        'grade' => match ($stored?->result_status) {
                            'graded' => $stored->grade,
                            'excused' => 'Excused',
                            default => '—',
                        },
                    ]];
                });
                $gpa = $termResult?->gpa === null ? null : (float) $termResult->gpa;

                return [
                    'student' => $student,
                    'subjects' => $subjectGrades,
                    'gpa' => $gpa,
                    'pass' => $termResult?->result_status === 'pass',
                    'rank' => null,
                ];
            });
            $rowsByStudent = $rows->keyBy(fn ($row) => $row['student']->id);
            $rankedCount = 0;
            foreach ($rows->filter(fn ($row) => $row['gpa'] !== null)->groupBy(fn ($row) => $row['student']->class_id) as $sectionRows) {
                $ranked = $sectionRows->sortByDesc('gpa')->values();
                $rankedCount += $ranked->count();
                $rank = 0;
                $previous = null;
                foreach ($ranked as $index => $row) {
                    if ($previous === null || (float) $row['gpa'] !== (float) $previous) {
                        $rank = $index + 1;
                    }
                    $previous = $row['gpa'];
                    $rankedRow = $rowsByStudent->get($row['student']->id);
                    $rankedRow['rank'] = $rank;
                    $rowsByStudent->put($row['student']->id, $rankedRow);
                }
            }
            $rows = $rowsByStudent->values();
            $rankedRows = $rows->filter(fn ($row) => $row['gpa'] !== null);
            $stats = [
                'count' => $rows->count(),
                'graded' => $rankedCount,
                'passed' => $rows->where('pass', true)->count(),
                'average_gpa' => $rankedRows->isNotEmpty() ? round($rankedRows->avg('gpa'), 2) : null,
            ];
        }

        return [$classes, $terms, $programs, $classId, $termId, $programId, $subjects, $rows, $stats];
    }
}
