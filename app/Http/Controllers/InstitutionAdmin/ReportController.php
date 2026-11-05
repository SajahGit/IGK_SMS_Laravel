<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('Reports.ReportStudents');
    }

    public function studentRegister(Request $request)
    {
        $students = \App\Models\Student::with(['user', 'class'])->when($request->class_id, fn($q)=>$q->where('class_id', $request->class_id))->get();
        if ($request->has('export')) {
            return \App\Support\CsvStreamer::download($students, ['Admission No','Name','Class'], 'student-register.csv');
        }
        return view('Reports.ReportEnrollmentRegister', compact('students'));
    }
}
