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

        return view('student.StudentDashboard', [
            'student' => $student,
            'summary' => SchoolHelper::attendanceSummary($student->id),
            'average' => SchoolHelper::studentAverage($student->id),
            'latestResults' => Result::with('exam.subject')->where('student_id', $student->id)
                ->latest()->take(5)->get(),
        ]);
    }
}
