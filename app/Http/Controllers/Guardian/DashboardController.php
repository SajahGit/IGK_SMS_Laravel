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

        $children = $guardian->students()->with([
            'user',
            'class',
            'transportAssignments' => fn ($query) => $query
                ->where('status', 'active')
                ->whereDate('start_date', '<=', today())
                ->where(fn ($nested) => $nested->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                ->whereHas('academicYear', fn ($nested) => $nested->where('is_current', true))
                ->with(['route.vehicle', 'stop', 'academicYear']),
        ])->get()->map(function ($student) {
            $student->attendance_percentage = SchoolHelper::attendancePercentage($student->id);
            $student->average = SchoolHelper::studentAverage($student->id);
            $student->transport_assignment = $student->transportAssignments->first();

            return $student;
        });

        return view('Guardian.GuardianDashboard', compact('children'));
    }
}
