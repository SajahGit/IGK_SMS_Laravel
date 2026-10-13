<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Term;
use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
    public function index(Request $request)
    {
        $mine = $this->myClassSubjects();

        $sessions = AttendanceSession::with(['classSubject.subject', 'classSubject.section.classGroup'])
            ->withCount('records')
            ->whereIn('class_subject_id', $mine->pluck('id'))
            ->when($request->filled('class_subject_id'), fn ($q) => $q->where('class_subject_id', $request->class_subject_id))
            ->orderByDesc('session_date')
            ->paginate(20)
            ->withQueryString();

        return view('Teacher.Attendance.Index', ['sessions' => $sessions, 'mine' => $mine]);
    }

    public function create()
    {
        return view('Teacher.Attendance.Create', ['mine' => $this->myClassSubjects()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_subject_id' => ['required', 'exists:class_subjects,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        abort_unless($request->user()->teachesClassSubject((int) $data['class_subject_id']), 403, 'This is not your class.');

        $session = AttendanceSession::firstOrCreate(
            ['class_subject_id' => $data['class_subject_id'], 'session_date' => $data['session_date']],
            ['taken_by' => $request->user()->id, 'status' => 'open']
        );

        return redirect()->route('teacher.attendance.show', $session);
    }

    public function show(AttendanceSession $session)
    {
        abort_unless(auth()->user()->teachesClassSubject($session->class_subject_id), 403, 'This is not your class.');

        $session->load(['classSubject.subject', 'classSubject.section.classGroup']);

        $students = $session->classSubject->section->students()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $records = $session->records()->get()->keyBy('student_id');

        return view('Teacher.Attendance.Show', compact('session', 'students', 'records'));
    }

    private function myClassSubjects()
    {
        $term = Term::current();

        return ClassSubject::with(['section.classGroup', 'subject'])
            ->where('staff_id', auth()->user()->staff?->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->get();
    }
}
