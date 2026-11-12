<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Support\SchoolHelper;

class DashboardController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $student->load(['class', 'guardian.user']);
        $transportAssignment = $student->transportAssignments()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->whereHas('academicYear', fn ($query) => $query->where('is_current', true))
            ->whereHas('route')
            ->whereHas('stop')
            ->with(['route.vehicle', 'stop', 'academicYear'])
            ->first();

        return view('Student.StudentDashboard', [
            'student' => $student,
            'summary' => SchoolHelper::attendanceSummary($student->id),
            'average' => SchoolHelper::studentAverage($student->id),
            'transportAssignment' => $transportAssignment,
            'latestResults' => Result::with('exam.subject')->where('student_id', $student->id)
                ->latest()->take(5)->get(),
        ]);
    }
}
