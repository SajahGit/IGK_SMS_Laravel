<?php

namespace App\Http\Controllers\Student;

use App\Services\StudentReportService;

class ResultController extends StudentBaseController
{
    public function index(StudentReportService $reports)
    {
        $student = $this->me();

        return view('Student.Results.Index', [
            'student' => $student,
            'results' => $reports->results($student),
        ]);
    }
}
