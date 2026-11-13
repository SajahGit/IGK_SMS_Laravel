<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Program;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Services\GradeScaleService;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectPerformanceReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $terms, $programs, $classId, $termId, $programId, $subjects, $subjectId, $subjectOptions, $gradeLabels] = $this->data($request);

        return view('Reports.ReportSubjectPerformance', compact(
            'classes', 'terms', 'programs', 'classId', 'termId', 'programId', 'subjects', 'subjectId', 'subjectOptions', 'gradeLabels'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        [, , , , , , $subjects, , , $gradeLabels] = $this->data($request);

        return response()->streamDownload(function () use ($subjects, $gradeLabels) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Subject',
                'Credit Hours',
                'Total Exams',
                'Students',
                'Average (%)',
                'Pass Rate',
                'Highest (%)',
                'Lowest (%)',
                'Grade Distribution ('.implode('/', $gradeLabels).')',
            ]);
            foreach ($subjects as $row) {
                fputcsv($out, [
                    $row['subject']->name.' ('.$row['subject']->code.')',
                    $row['subject']->credit_hours,
                    $row['exams_count'],
                    $row['results_count'],
                    $row['avg_percentage'].'%',
                    $row['pass_rate'].'%',
                    $row['highest_percentage'],
                    $row['lowest_percentage'],
                    implode('/', array_map(fn ($grade) => $row['grades'][$grade] ?? 0, $gradeLabels)),
                ]);
            }
            fclose($out);
        }, 'subject-performance-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.academic'), 403);
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);
        $allowedClasses = SchoolHelper::allowedClasses($request->user());
        $classId = $validated['class_id'] ?? null;
        $termId = $validated['term_id'] ?? null;
        $programId = $validated['program_id'] ?? null;
        $subjectId = $validated['subject_id'] ?? null;
        if ($classId) {
            abort_unless($allowedClasses->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }
        $classes = $allowedClasses->when($programId, fn ($items) => $items->filter(
            fn ($class) => (int) $class->program_id === (int) $programId
        ))->when($classId, fn ($items) => $items->where('id', (int) $classId))->values();
        $classes = $classes->load('program.gradeScale.items');
        $gradeLabels = $classes
            ->flatMap(fn ($class) => GradeScaleService::items($class->program?->gradeScale)->pluck('grade'))
            ->unique()
            ->values()
            ->all();
        $subjectResults = SubjectResult::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('result_status', 'graded')
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('term_results')
                    ->whereColumn('term_results.term_id', 'subject_results.term_id')
                    ->whereColumn('term_results.class_id', 'subject_results.class_id')
                    ->whereColumn('term_results.student_id', 'subject_results.student_id')
                    ->where('term_results.publication_status', 'published');
            })
            ->with('subject')
            ->get()
            ->groupBy('subject_id');
        $examCounts = Exam::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('is_published', true)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->selectRaw('subject_id, COUNT(*) as total')
            ->groupBy('subject_id')
            ->pluck('total', 'subject_id');
        $subjects = $subjectResults->map(function ($rows) use ($gradeLabels, $examCounts, $classes) {
            $subject = $rows->first()->subject;
            $studentTermResults = $rows->unique(fn ($row) => $row->term_id.':'.$row->student_id);
            $grades = collect(array_fill_keys($gradeLabels, 0));
            foreach ($studentTermResults as $result) {
                if ($grades->has($result->grade)) {
                    $grades->put($result->grade, $grades->get($result->grade) + 1);
                }
            }
            $percentages = $studentTermResults->pluck('percentage')->map(fn ($value) => (float) $value);
            $passed = $studentTermResults->filter(fn ($result) => GradeScaleService::isPassing(
                (float) $result->percentage,
                $classes->firstWhere('id', $result->class_id)?->program?->gradeScale
            ))->count();

            return [
                'subject' => $subject,
                'exams_count' => (int) $examCounts->get($subject->id, 0),
                'results_count' => $studentTermResults->count(),
                'avg_percentage' => $percentages->isNotEmpty() ? round($percentages->avg(), 1) : 0.0,
                'pass_rate' => $studentTermResults->isNotEmpty() ? round($passed / $studentTermResults->count() * 100, 1) : 0.0,
                'highest_percentage' => $percentages->isNotEmpty() ? $percentages->max() : '—',
                'lowest_percentage' => $percentages->isNotEmpty() ? $percentages->min() : '—',
                'grades' => $grades->all(),
            ];
        })->sortBy(fn ($row) => $row['subject']->name)->values();

        return [
            SchoolHelper::allowedClasses($request->user()), Term::orderByDesc('start_date')->get(),
            Program::orderBy('name')->get(), $classId, $termId, $programId, $subjects, $subjectId,
            Subject::orderBy('name')->get(), $gradeLabels,
        ];
    }
}
