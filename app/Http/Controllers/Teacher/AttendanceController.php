<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\AttendanceSessionService;
use App\Support\AuditLogger;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $classes = $user->isTeacher()
            ? SchoolClass::whereIn('id', SchoolHelper::teacherClassIds($teacher))->orderBy('name')->orderBy('section')->get()
            : SchoolHelper::allowedClasses($user);

        $classId = $request->query('class_id');
        $date = $request->query('date', today()->toDateString());
        $startTime = $request->query('start_time');
        $sessionId = $request->query('session_id');
        $selectedSession = $sessionId ? AttendanceSession::with('classSubject')->findOrFail($sessionId) : null;
        if ($selectedSession) {
            abort_unless($classes->contains('id', $selectedSession->class_id), 403);
            $classId = $selectedSession->class_id;
            $date = $selectedSession->date->toDateString();
            $startTime = $selectedSession->start_time ? substr($selectedSession->start_time, 0, 5) : null;
        }
        $classSubjectId = $selectedSession?->class_subject_id ?? $request->query('class_subject_id');
        $students = collect();
        $existing = collect();
        $classSubjects = collect();
        $session = $selectedSession;

        if ($classId) {
            if ($user->isTeacher()) {
                SchoolHelper::authorizeTeacherClass($teacher, $classId);
            }

            $classSubjects = ClassSubject::with(['subject', 'term'])
                ->where('class_id', $classId)
                ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $teacher->id))
                ->when($user->isTeacher(), function ($query) {
                    $currentTermId = Term::where('is_current', true)->value('id');
                    $query->where(function ($terms) use ($currentTermId) {
                        $terms->whereNull('term_id');
                        if ($currentTermId) {
                            $terms->orWhere('term_id', $currentTermId);
                        }
                    });
                })
                ->orderBy('term_id')->orderBy('subject_id')->get();
            if ($classSubjectId) {
                abort_unless(
                    ClassSubject::whereKey($classSubjectId)->where('class_id', $classId)->exists(),
                    404
                );
            }
            $students = Student::with('user')
                ->whereIn('id', AttendanceSessionService::enrolledStudentIds(
                    (int) $classId,
                    $classSubjectId ? (int) $classSubjectId : null
                ))
                ->orderBy('admission_no')->get();
            $sessionQuery = AttendanceSession::where('class_id', $classId)->whereDate('date', $date);
            $classSubjectId
                ? $sessionQuery->where('class_subject_id', $classSubjectId)
                : $sessionQuery->whereNull('class_subject_id');
            if ($startTime) {
                $sessionQuery->where('start_time', 'like', $startTime.'%');
            } else {
                $sessionQuery->whereNull('start_time');
            }
            $session ??= $sessionQuery->first();
            if ($session) {
                $existing = Attendance::where('attendance_session_id', $session->id)->get()->keyBy('student_id');
            }
        }

        return view('Teacher.TeacherAttendanceCreate', [
            'classes' => $classes,
            'classId' => $classId,
            'date' => $date,
            'startTime' => $startTime,
            'classSubjectId' => $classSubjectId,
            'classSubjects' => $classSubjects,
            'students' => $students,
            'existing' => $existing,
            'session' => $session,
            'attendanceRoutePrefix' => $request->routeIs('admin.*') ? 'admin' : 'teacher',
        ]);
    }

    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->validated();
        $selectedSession = isset($data['attendance_session_id'])
            ? AttendanceSession::findOrFail($data['attendance_session_id'])
            : null;
        $classSubjectId = $selectedSession?->class_subject_id ?? ($data['class_subject_id'] ?? null);
        $activeStudentIds = AttendanceSessionService::enrolledStudentIds(
            (int) $data['class_id'],
            $classSubjectId ? (int) $classSubjectId : null
        )
            ->map(fn ($id) => (string) $id);
        $submittedStudentIds = collect(array_keys($data['status']))->map(fn ($id) => (string) $id);
        abort_unless(
            $submittedStudentIds->diff($activeStudentIds)->isEmpty()
                && $activeStudentIds->diff($submittedStudentIds)->isEmpty(),
            422,
            'Attendance must include every active student in this class.'
        );

        $session = DB::transaction(function () use ($data) {
            $session = isset($data['attendance_session_id'])
                ? AttendanceSession::findOrFail($data['attendance_session_id'])
                : AttendanceSessionService::create(
                    (int) $data['class_id'],
                    (string) $data['date'],
                    (string) $data['start_time'],
                    isset($data['class_subject_id']) ? (int) $data['class_subject_id'] : null,
                    (int) auth()->id()
                );

            foreach ($data['status'] as $studentId => $status) {
                $record = Attendance::firstOrNew([
                    'student_id' => $studentId,
                    'attendance_session_id' => $session->id,
                ]);
                $before = $record->exists ? $record->only(['status', 'remarks']) : null;
                $record->fill([
                    'class_id' => $data['class_id'],
                    'date' => $data['date'],
                    'status' => $status,
                    'remarks' => $data['remarks'][$studentId] ?? null,
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ]);
                $record->save();
                AuditLogger::log('attendance_marked', $record, $before, $record->only(['status', 'remarks']));
            }

            return $session;
        });

        return redirect()
            ->route(request()->routeIs('admin.*') ? 'admin.attendance.create' : 'teacher.attendance.create', [
                'session_id' => $session->id,
            ])
            ->with('success', 'Attendance saved.');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $classes = $user->isTeacher()
            ? SchoolClass::whereIn('id', SchoolHelper::teacherClassIds($teacher))->orderBy('name')->orderBy('section')->get()
            : SchoolHelper::allowedClasses($user);

        $classId = $request->query('class_id');
        $from = $request->query('from', today()->subDays(30)->toDateString());
        $to = $request->query('to', today()->toDateString());
        $byDate = collect();

        if ($classId) {
            if ($user->isTeacher()) {
                SchoolHelper::authorizeTeacherClass($teacher, $classId);
            }

            $byDate = AttendanceSession::with(['classSubject.subject', 'taker'])
                ->where('class_id', $classId)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->withCount('records')
                ->orderByDesc('date')->orderBy('session_label')
                ->get()
                ->map(function ($session) {
                    $counts = $session->records()->selectRaw('status, COUNT(*) as total')
                        ->groupBy('status')->pluck('total', 'status');
                    $session->counts = $counts;

                    return $session;
                });
        }

        return view('Teacher.TeacherAttendanceIndex', [
            'classes' => $classes,
            'classId' => $classId,
            'from' => $from,
            'to' => $to,
            'byDate' => $byDate,
            'attendanceRoutePrefix' => $request->routeIs('admin.*') ? 'admin' : 'teacher',
        ]);
    }
}
