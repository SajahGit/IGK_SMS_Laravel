<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Term;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $term = Term::current();
        $staffId = $user->staff?->id;

        $classSubjects = ClassSubject::with(['section.classGroup', 'subject'])
            ->where('staff_id', $staffId)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->get();

        $todaySessions = AttendanceSession::with(['classSubject.subject', 'classSubject.section.classGroup'])
            ->whereIn('class_subject_id', $classSubjects->pluck('id'))
            ->whereDate('session_date', today())
            ->get();

        return view('Teacher.Dashboard.Index', [
            'term' => $term,
            'classSubjects' => $classSubjects,
            'todaySessions' => $todaySessions,
            'draftExams' => Exam::where('created_by', $user->id)->where('status', 'draft')->count(),
        ]);
    }
}
