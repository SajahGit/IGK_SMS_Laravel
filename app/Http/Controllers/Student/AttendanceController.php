<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $request->validate(['month' => ['nullable', 'regex:/^\d{4}-\d{2}$/']]);
        $month = $request->query('month');

        $query = Attendance::where('student_id', $student->id);
        $from = $to = null;

        if ($month) {
            $from = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $to = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $query->where('date', '>=', $from)->where('date', '<=', $to);
        }

        return view('student.StudentAttendance', [
            'records' => $query->orderByDesc('date')->paginate(15)->withQueryString(),
            'summary' => SchoolHelper::attendanceSummary($student->id, $from, $to),
            'month' => $month,
        ]);
    }
}
