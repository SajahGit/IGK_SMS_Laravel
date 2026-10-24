<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $classId, $from, $to, $rows] = $this->data($request);

        return view('reports.ReportAttendance', compact('classes', 'classId', 'from', 'to', 'rows'));
    }

    public function csv(Request $request)
    {
        [, , , , $rows] = $this->data($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Name', 'Total Days', 'Present', 'Absent', 'Late', 'Percentage']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['student']->admission_no, $r['student']->user->name,
                    $r['summary']['total'], $r['summary']['present'], $r['summary']['absent'],
                    $r['summary']['late'], $r['summary']['percentage'],
                ]);
            }
            fclose($out);
        }, 'attendance-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $request->query('class_id');
        $from = $request->query('from', today()->subDays(30)->toDateString());
        $to = $request->query('to', today()->toDateString());
        $rows = collect();

        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');

            $students = Student::with('user')->where('class_id', $classId)->orderBy('admission_no')->get();

            $counts = Attendance::where('class_id', $classId)
                ->where('date', '>=', $from)->where('date', '<=', $to)
                ->selectRaw('student_id, status, COUNT(*) as total')
                ->groupBy('student_id', 'status')
                ->get()
                ->groupBy('student_id');

            $rows = $students->map(fn ($student) => [
                'student' => $student,
                'summary' => SchoolHelper::summaryFromCounts(
                    isset($counts[$student->id]) ? $counts[$student->id]->pluck('total', 'status') : []
                ),
            ]);
        }

        return [$classes, $classId, $from, $to, $rows];
    }
}
