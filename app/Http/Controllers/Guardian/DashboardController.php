<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Support\SchoolHelper;

class DashboardController extends Controller
{
    public function index()
    {
        $guardian = auth()->user()->guardian;
        abort_unless($guardian, 403, 'No parent profile is linked to this account.');

        $children = $guardian->students()->with(['user', 'class'])->get()->map(function ($student) {
            $student->attendance_percentage = SchoolHelper::attendancePercentage($student->id);
            $student->average = SchoolHelper::studentAverage($student->id);

            return $student;
        });

        return view('guardian.GuardianDashboard', compact('children'));
    }
}
