<?php

namespace App\Http\Controllers\Student;

use App\Services\StudentReportService;

class DashboardController extends StudentBaseController
{
    public function index(StudentReportService $reports)
    {
        $student = $this->me();

        return view('Student.Dashboard.Index', [
            'student' => $student,
            'attendance' => $reports->attendanceSummary($student),
            'latest' => $reports->results($student)->first(),
        ]);
    }
}
