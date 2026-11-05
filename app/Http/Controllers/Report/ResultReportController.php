<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Services\GradingService;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class ResultReportController extends Controller
{
    public function index(Request $request)
    {
        [$exams, $exam, $rows, $stats] = $this->data($request);

        return view('Reports.ReportResults', compact('exams', 'exam', 'rows', 'stats'));
    }

    public function csv(Request $request)
    {
        [, $exam, $rows] = $this->data($request);
        abort_unless($exam, 404);

        return response()->streamDownload(function () use ($rows, $exam) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Exam: '.$exam->name, 'Section: '.$exam->class->full_name, 'Subject: '.$exam->subject->name, 'Max marks: '.$exam->max_marks]);
            fputcsv($out, ['Rank', 'Admission No', 'Name', 'Marks', 'Maximum', 'Percentage', 'Grade', 'Absent']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['rank'], $r['result']->student->admission_no, $r['result']->student->user->name,
                    $r['result']->marks, $exam->max_marks, $r['percentage'], $r['grade'], $r['absent'] ? 'Yes' : 'No',
                ]);
            }
            fclose($out);
        }, 'result-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.academic'), 403);
        $classes = SchoolHelper::allowedClasses($request->user());

        $exams = Exam::with(['class', 'subject'])
            ->whereIn('class_id', $classes->pluck('id'))
            ->orderByDesc('exam_date')
            ->get();

        $exam = null;
        $rows = collect();
        $stats = null;

        if ($examId = $request->query('exam_id')) {
            $exam = $exams->firstWhere('id', (int) $examId);
            abort_unless($exam, 403, 'You cannot view this exam.');

            $results = Result::with('student.user')->where('exam_id', $exam->id)->orderByDesc('marks')->get();

            $rank = 0;
            $previous = null;
            $rows = $results->values()->map(function ($result, $index) use (&$rank, &$previous, $exam) {
                $marks = $result->is_absent ? 0.0 : (float) ($result->marks ?? 0);
                if ($previous === null || $marks !== (float) $previous) {
                    $rank = $index + 1;
                }
                $previous = $marks;
                $percentage = $result->is_absent ? 0.0 : SchoolHelper::resultPercentage($result->marks, $exam->max_marks);

                return [
                    'rank' => $rank,
                    'result' => $result,
                    'percentage' => $percentage,
                    'grade' => SchoolHelper::gradeFor($percentage),
                    'absent' => $result->is_absent,
                ];
            });

            $stats = [
                'count' => $rows->count(),
                'average' => $rows->count() ? round($rows->avg('percentage'), 1) : 0,
                'highest' => $results->max('marks'),
                'lowest' => $results->min('marks'),
                'passed' => $rows->filter(fn ($r) => $r['percentage'] !== null && GradingService::isPassing($r['percentage']))->count(),
            ];
        }

        return [$exams, $exam, $rows, $stats];
    }
}
