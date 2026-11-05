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
        $classId = $validated['class_id'] ?? null;
        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
            $classes = $classes->where('id', (int) $classId)->values();
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

        $summary = $classes->map(function ($class) use ($termId, $subjectId, $from, $to, $threshold) {
            $sessions = AttendanceSession::query()
                ->where('class_id', $class->id)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->when($termId, fn ($query) => $query->where('term_id', $termId))
                ->when($subjectId, fn ($query) => $query->whereHas('classSubject', fn ($subject) => $subject->where('subject_id', $subjectId)))
                ->get();
            $records = Attendance::whereIn('attendance_session_id', $sessions->pluck('id'))->get();
            $presentAndLate = $records->whereIn('status', ['present', 'late'])->count();
            $eligible = $records->whereNotIn('status', ['excused'])->count();
            $byStudent = $records->groupBy('student_id');
            $activeStudentQuery = Student::query()
                ->where('class_id', $class->id)
                ->where('status', 'active');
            if ($subjectId) {
                $enrolledStudentIds = DB::table('student_subject_enrollments')
                    ->join('class_subject', 'class_subject.id', '=', 'student_subject_enrollments.class_subject_id')
                    ->where('class_subject.class_id', $class->id)
                    ->where('class_subject.subject_id', $subjectId)
                    ->where('student_subject_enrollments.status', 'enrolled')
                    ->pluck('student_subject_enrollments.student_id');
                $activeStudentQuery->whereIn('id', $enrolledStudentIds);
            }
            $activeStudents = $activeStudentQuery->get(['id']);
            $belowThreshold = $activeStudents->filter(function ($student) use ($byStudent, $threshold) {
                $counts = $byStudent->get($student->id, collect())->countBy('status');
                $attended = (int) ($counts['present'] ?? 0) + (int) ($counts['late'] ?? 0);
                $eligibleRecords = $counts->except('excused')->sum();
                $percentage = $eligibleRecords > 0 ? ($attended / $eligibleRecords) * 100 : 0;

                return $percentage < $threshold;
            })->count();

            return [
                'class' => $class,
                'subject' => $subjectId ? ($sessions->first()?->classSubject?->subject?->name ?? '—') : 'All subjects',
                'sessions' => $sessions->count(),
                'percentage' => $eligible > 0 ? round(($presentAndLate / $eligible) * 100, 1) : 0.0,
                'below_threshold' => $belowThreshold,
            ];
        });

        return [
            SchoolHelper::allowedClasses($request->user()), $classId, $termId, $subjectId,
            $from, $to, $threshold, Term::orderByDesc('start_date')->get(), $subjects, $summary,
        ];
    }
}
