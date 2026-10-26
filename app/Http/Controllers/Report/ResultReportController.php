<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class ResultReportController extends Controller
{
    public function index(Request $request)
    {
        [$exams, $exam, $rows, $stats] = $this->data($request);

        return view('reports.ReportResults', compact('exams', 'exam', 'rows', 'stats'));
    }

    public function csv(Request $request)
    {
        [, $exam, $rows] = $this->data($request);
        abort_unless($exam, 404);

        return response()->streamDownload(function () use ($rows, $exam) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Exam: '.$exam->name, 'Subject: '.$exam->subject->name, 'Max marks: '.$exam->max_marks]);
            fputcsv($out, ['Rank', 'Admission No', 'Name', 'Marks', 'Percentage', 'Grade']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['rank'], $r['result']->student->admission_no, $r['result']->student->user->name,
                    $r['result']->marks, $r['percentage'], $r['grade'],
                ]);
            }
            fclose($out);
        }, 'result-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
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
                if ($previous === null || (float) $result->marks !== (float) $previous) {
                    $rank = $index + 1; // same marks share the same rank
                }
                $previous = $result->marks;
                $percentage = SchoolHelper::resultPercentage($result->marks, $exam->max_marks);

                return [
                    'rank' => $rank,
                    'result' => $result,
                    'percentage' => $percentage,
                    'grade' => SchoolHelper::gradeFor($percentage),
                ];
            });

            $stats = [
                'count' => $rows->count(),
                'average' => $rows->count() ? round($results->avg('marks'), 1) : 0,
                'highest' => $results->max('marks'),
                'lowest' => $results->min('marks'),
                'passed' => $rows->filter(fn ($r) => $r['percentage'] >= 40)->count(),
            ];
        }

        return [$exams, $exam, $rows, $stats];
    }
}
