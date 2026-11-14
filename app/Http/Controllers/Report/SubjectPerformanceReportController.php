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
        $passingRanges = [];
        $passingBindings = [];
        foreach ($classes as $class) {
            foreach (GradeScaleService::items($class->program?->gradeScale)->where('is_pass', true) as $item) {
                $passingRanges[] = '(class_id = ? AND COALESCE(percentage, 0) BETWEEN ? AND ?)';
                array_push($passingBindings, $class->id, $item->min_percent, $item->max_percent);
            }
        }
        $passExpression = $passingRanges === []
            ? '0'
            : 'CASE WHEN '.implode(' OR ', $passingRanges).' THEN 1 ELSE 0 END';

        $subjectResultQuery = SubjectResult::query()
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
            });
        $subjectResultAggregates = (clone $subjectResultQuery)
            ->select('subject_id', 'class_id', 'grade')
            ->selectRaw('COUNT(*) as results_count')
            ->selectRaw('SUM(COALESCE(percentage, 0)) as percentage_sum')
            ->selectRaw('MIN(COALESCE(percentage, 0)) as lowest_percentage')
            ->selectRaw('MAX(COALESCE(percentage, 0)) as highest_percentage')
            ->selectRaw("SUM({$passExpression}) as passed_count", $passingBindings)
            ->groupBy('subject_id', 'class_id', 'grade')
            ->get();
        $examCounts = Exam::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('is_published', true)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->selectRaw('subject_id, COUNT(*) as total')
            ->groupBy('subject_id')
            ->pluck('total', 'subject_id');
        $subjectIds = $subjectResultAggregates->pluck('subject_id')->unique();
        $subjectModels = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get(['id', 'name', 'code', 'credit_hours'])
            ->keyBy('id');
        $subjectsById = [];
        foreach ($subjectResultAggregates as $aggregate) {
            $id = (int) $aggregate->subject_id;
            $subjectsById[$id] ??= [
                'results_count' => 0,
                'percentage_sum' => 0.0,
                'passed_count' => 0,
                'highest_percentage' => null,
                'lowest_percentage' => null,
                'grades' => array_fill_keys($gradeLabels, 0),
            ];
            $subjectsById[$id]['results_count'] += (int) $aggregate->results_count;
            $subjectsById[$id]['percentage_sum'] += (float) $aggregate->percentage_sum;
            $subjectsById[$id]['passed_count'] += (int) $aggregate->passed_count;
            $subjectsById[$id]['highest_percentage'] = max(
                $subjectsById[$id]['highest_percentage'] ?? (float) $aggregate->highest_percentage,
                (float) $aggregate->highest_percentage
            );
            $subjectsById[$id]['lowest_percentage'] = min(
                $subjectsById[$id]['lowest_percentage'] ?? (float) $aggregate->lowest_percentage,
                (float) $aggregate->lowest_percentage
            );
            if (array_key_exists($aggregate->grade, $subjectsById[$id]['grades'])) {
                $subjectsById[$id]['grades'][$aggregate->grade] += (int) $aggregate->results_count;
            }
        }
        $subjects = collect($subjectsById)->map(function ($aggregate, $id) use ($subjectModels, $examCounts) {
            $count = $aggregate['results_count'];
            $subject = $subjectModels->get($id);

            return [
                'subject' => $subject,
                'exams_count' => (int) $examCounts->get($id, 0),
                'results_count' => $count,
                'avg_percentage' => $count > 0 ? round($aggregate['percentage_sum'] / $count, 1) : 0.0,
                'pass_rate' => $count > 0 ? round($aggregate['passed_count'] / $count * 100, 1) : 0.0,
                'highest_percentage' => $aggregate['highest_percentage'] ?? '—',
                'lowest_percentage' => $aggregate['lowest_percentage'] ?? '—',
                'grades' => $aggregate['grades'],
            ];
        })->filter(fn ($row) => $row['subject'] !== null)->sortBy(fn ($row) => $row['subject']->name)->values();

        return [
            SchoolHelper::allowedClasses($request->user()), Term::orderByDesc('start_date')->get(),
            Program::orderBy('name')->get(), $classId, $termId, $programId, $subjects, $subjectId,
            Subject::orderBy('name')->get(), $gradeLabels,
        ];
    }
}
