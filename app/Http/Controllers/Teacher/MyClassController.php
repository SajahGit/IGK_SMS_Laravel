<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassSubject;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class MyClassController extends Controller
{
    public function index()
    {
        $term = Term::current();

        $items = ClassSubject::with(['section.classGroup', 'subject'])
            ->where('staff_id', auth()->user()->staff?->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->get()
            ->each(fn ($cs) => $cs->student_count = $cs->section?->students()->count() ?? 0);

        return view('Teacher.MyClasses.Index', ['items' => $items, 'term' => $term]);
    }

    public function show(ClassSubject $classSubject)
    {
        abort_unless(auth()->user()->teachesClassSubject($classSubject->id), 403, 'This is not your class.');

        $classSubject->load(['section.classGroup', 'subject']);
        $students = $classSubject->section->students()->where('status', 'active')->orderBy('first_name')->get();

        // attendance summary of each student for THIS class-subject
        $stats = AttendanceRecord::query()
            ->join('attendance_sessions as s', 's.id', '=', 'attendance_records.attendance_session_id')
            ->where('s.class_subject_id', $classSubject->id)
            ->groupBy('attendance_records.student_id')
            ->select(
                'attendance_records.student_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN attendance_records.status IN ('present','late') THEN 1 ELSE 0 END) as attended")
            )
            ->get()
            ->keyBy('student_id');

        return view('Teacher.MyClasses.Show', compact('classSubject', 'students', 'stats'));
    }
}
