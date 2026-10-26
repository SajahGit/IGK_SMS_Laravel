<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Support\SchoolHelper;

class ResultController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get()
            ->sortByDesc(fn ($r) => $r->exam->exam_date);

        return view('student.StudentResults', [
            'results' => $results,
            'average' => SchoolHelper::studentAverage($student->id),
        ]);
    }
}
