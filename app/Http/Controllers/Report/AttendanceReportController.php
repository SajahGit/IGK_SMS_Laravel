<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Teacher;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $classId, $subjectId, $teacherId, $from, $to, $subjects, $teachers, $rows] = $this->data($request);

        return view('Reports.ReportAttendance', compact(
            'classes', 'classId', 'subjectId', 'teacherId', 'from', 'to', 'subjects', 'teachers', 'rows'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        [, , , , , , , , $rows] = $this->data($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Class/Section', 'Subject', 'Teacher', 'Admission No', 'Student', 'Status', 'Remarks', 'Taken by', 'Time']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['session']->date->toDateString(),
                    $row['session']->schoolClass->full_name,
                    $row['session']->classSubject?->subject?->name ?? $row['session']->session_label,
                    $row['session']->classSubject?->teacher?->user?->name ?? '—',
                    $row['record']->student->admission_no,
                    $row['record']->student->user->name,
                    ucfirst($row['record']->status),
                    $row['record']->remarks,
                    $row['session']->taker?->name ?? '—',
                    $row['session']->start_time
                        ? substr($row['session']->start_time, 0, 5)
                        : ($row['session']->created_at?->format('H:i') ?? '—'),
                ]);
            }
            fclose($out);
        }, "daily-attendance-{$request->query('from')}-to-{$request->query('to')}.csv", ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.attendance'), 403);

        $validated = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'teacher_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $validated['class_id'] ?? null;
        $subjectId = $validated['subject_id'] ?? null;
        $teacherId = $validated['teacher_id'] ?? null;
        $pdfLimit = $request->attributes->get('pdf_export_limit');
        $from = $validated['from'] ?? today()->subDays(30)->toDateString();
        $to = $validated['to'] ?? today()->toDateString();

        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }

        $subjects = ClassSubject::query()
            ->with('subject')
            ->whereIn('class_id', $classes->pluck('id'))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
        $teachers = Teacher::with('user')
            ->whereHas('classSubjects', fn ($query) => $query->whereIn('class_id', $classes->pluck('id')))
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user?->name)
            ->values();

        $sessions = collect();
        $rows = collect();
        if ($classId) {
            if (is_int($pdfLimit)) {
                $attendanceRows = Attendance::query()
                    ->select(['id', 'attendance_session_id', 'student_id', 'status', 'remarks'])
                    ->with([
                        'session:id,class_id,class_subject_id,taken_by,date,start_time,session_label,created_at',
                        'session.schoolClass:id,name,section',
                        'session.classSubject:id,subject_id,teacher_id',
                        'session.classSubject.subject:id,name',
                        'session.classSubject.teacher:id,user_id',
                        'session.classSubject.teacher.user:id,name',
                        'session.taker:id,name',
                        'student:id,user_id,admission_no',
                        'student.user:id,name',
                    ])
                    ->whereHas('session', fn ($query) => $query
                        ->where('class_id', $classId)
                        ->whereDate('date', '>=', $from)
                        ->whereDate('date', '<=', $to)
                        ->when($subjectId, fn ($sessionQuery) => $sessionQuery->whereHas(
                            'classSubject',
                            fn ($subjectQuery) => $subjectQuery->where('subject_id', $subjectId)
                        ))
                        ->when($teacherId, fn ($sessionQuery) => $sessionQuery->whereHas(
                            'classSubject',
                            fn ($subjectQuery) => $subjectQuery->where('teacher_id', $teacherId)
                        )))
                    ->orderBy('attendance_session_id')
                    ->orderBy('id')
                    ->limit($pdfLimit)
                    ->get();
                $rows = $attendanceRows->map(fn ($record) => [
                    'session' => $record->session,
                    'record' => $record,
                ]);
            } else {
                $sessions = AttendanceSession::with([
                    'schoolClass',
                    'classSubject.subject',
                    'classSubject.teacher.user',
                    'taker',
                    'records.student.user',
                ])
                    ->where('class_id', $classId)
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to)
                    ->when($subjectId, fn ($query) => $query->whereHas('classSubject', fn ($subjectQuery) => $subjectQuery->where('subject_id', $subjectId)))
                    ->when($teacherId, fn ($query) => $query->whereHas('classSubject', fn ($subjectQuery) => $subjectQuery->where('teacher_id', $teacherId)))
                    ->orderBy('date')
                    ->orderBy('id')
                    ->get();
            }
        }

        if (! is_int($pdfLimit)) {
            $rows = $sessions->flatMap(fn ($session) => $session->records->map(fn ($record) => [
                'session' => $session,
                'record' => $record,
            ]))->values();
        }

        return [$classes, $classId, $subjectId, $teacherId, $from, $to, $subjects, $teachers, $rows];
    }
}
