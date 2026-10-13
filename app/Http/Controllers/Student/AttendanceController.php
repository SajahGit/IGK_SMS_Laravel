<?php

namespace App\Http\Controllers\Student;

use App\Services\StudentReportService;

class AttendanceController extends StudentBaseController
{
    public function index(StudentReportService $reports)
    {
        $student = $this->me();

        return view('Student.Attendance.Index', [
            'student' => $student,
            'summary' => $reports->attendanceSummary($student),
            'records' => $reports->attendanceList($student),
        ]);
    }
}
