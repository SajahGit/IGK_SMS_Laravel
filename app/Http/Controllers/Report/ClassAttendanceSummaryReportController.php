<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\Term;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassAttendanceSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $classId, $termId, $subjectId, $from, $to, $threshold, $terms, $subjects, $summary] = $this->data($request);

        return view('Reports.ReportClassAttendanceSummary', compact(
            'classes', 'classId', 'termId', 'subjectId', 'from', 'to', 'threshold', 'terms', 'subjects', 'summary'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        [, , , , $from, $to, $threshold, , , $summary] = $this->data($request);

        return response()->streamDownload(function () use ($summary, $threshold) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Class & Section', 'Subject', 'Sessions Held', 'Average Attendance', "Students Below {$threshold}%"]);
            foreach ($summary as $row) {
                fputcsv($out, [
                    $row['class']->full_name,
                    $row['subject'],
                    $row['sessions'],
                    $row['percentage'].'%',
                    $row['below_threshold'],
                ]);
            }
            fclose($out);
        }, "class-attendance-summary-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.attendance'), 403);
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $classes = SchoolHelper::allowedClasses($request->user());
        $pdfLimit = $request->attributes->get('pdf_export_limit');
        $classId = $validated['class_id'] ?? null;
        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
            $classes = $classes->where('id', (int) $classId)->values();
        }
        if (is_int($pdfLimit)) {
            $classes = $classes->take($pdfLimit)->values();
        }
        $termId = $validated['term_id'] ?? null;
        $subjectId = $validated['subject_id'] ?? null;
        $from = $validated['from'] ?? today()->subDays(30)->toDateString();
        $to = $validated['to'] ?? today()->toDateString();
        $threshold = (float) ($validated['threshold'] ?? 75);
        $subjects = ClassSubject::with('subject')
            ->whereIn('class_id', SchoolHelper::allowedClasses($request->user())->pluck('id'))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $sessionQuery = AttendanceSession::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->when($subjectId, fn ($query) => $query->whereHas('classSubject', fn ($subject) => $subject->where('subject_id', $subjectId)));
        $sessionCounts = (clone $sessionQuery)
            ->select('class_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');
        $sessionIds = (clone $sessionQuery)->select('id');
        $attendanceTotals = Attendance::query()
            ->whereIn('attendance_session_id', $sessionIds)
            ->select('class_id')
            ->selectRaw("SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_and_late")
            ->selectRaw("SUM(CASE WHEN status <> 'excused' THEN 1 ELSE 0 END) as eligible")
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');
        $activeStudentQuery = Student::query()
            ->whereIn('students.class_id', $classes->pluck('id'))
            ->where('students.status', 'active');
        if ($subjectId) {
            $enrolledStudentIds = DB::table('student_subject_enrollments')
                ->join('class_subject', 'class_subject.id', '=', 'student_subject_enrollments.class_subject_id')
                ->whereIn('class_subject.class_id', $classes->pluck('id'))
                ->where('class_subject.subject_id', $subjectId)
                ->where('student_subject_enrollments.status', 'enrolled')
                ->select('student_subject_enrollments.student_id');
            $activeStudentQuery->whereIn('students.id', $enrolledStudentIds);
        }
        $attendanceByStudent = Attendance::query()
            ->whereIn('attendance_session_id', $sessionIds)
            ->select('class_id', 'student_id')
            ->selectRaw("SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as attended")
            ->selectRaw("SUM(CASE WHEN status <> 'excused' THEN 1 ELSE 0 END) as eligible")
            ->groupBy('class_id', 'student_id');
        $belowThresholdCounts = (clone $activeStudentQuery)
            ->leftJoinSub($attendanceByStudent, 'student_attendance', function ($join) {
                $join->on('students.id', '=', 'student_attendance.student_id')
                    ->on('students.class_id', '=', 'student_attendance.class_id');
            })
            ->whereRaw(
                '(CASE WHEN COALESCE(student_attendance.eligible, 0) > 0 THEN COALESCE(student_attendance.attended, 0) * 100.0 / student_attendance.eligible ELSE 0 END) < CAST(? AS DECIMAL(5, 2))',
                [$threshold]
            )
            ->selectRaw('students.class_id as class_id_key')
            ->selectRaw('COUNT(DISTINCT students.id) as total')
            ->groupBy('students.class_id')
            ->get()
            ->pluck('total', 'class_id_key');
        $summary = $classes->map(function ($class) use ($sessionCounts, $attendanceTotals, $belowThresholdCounts, $subjectId, $subjects) {
            $totals = $attendanceTotals->get($class->id);
            $presentAndLate = (int) ($totals?->present_and_late ?? 0);
            $eligible = (int) ($totals?->eligible ?? 0);

            return [
                'class' => $class,
                'subject' => $subjectId ? ($subjects->firstWhere('id', $subjectId)?->name ?? '—') : 'All subjects',
                'sessions' => (int) $sessionCounts->get($class->id, 0),
                'percentage' => $eligible > 0 ? round(($presentAndLate / $eligible) * 100, 1) : 0.0,
                'below_threshold' => (int) $belowThresholdCounts->get($class->id, 0),
            ];
        });

        return [
            SchoolHelper::allowedClasses($request->user()), $classId, $termId, $subjectId,
            $from, $to, $threshold, Term::orderByDesc('start_date')->get(), $subjects, $summary,
        ];
    }
}
