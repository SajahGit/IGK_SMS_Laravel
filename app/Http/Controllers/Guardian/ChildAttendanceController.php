<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChildAttendanceController extends Controller
{
    public function show(Request $request, Student $student)
    {
        $this->authorizeChild($student);

        $request->validate(['month' => ['nullable', 'regex:/^\d{4}-\d{2}$/']]);
        $month = $request->query('month');

        $query = Attendance::where('student_id', $student->id);
        $from = $to = null;

        if ($month) {
            $from = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $to = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $query->where('date', '>=', $from)->where('date', '<=', $to);
        }

        return view('guardian.GuardianAttendance', [
            'student' => $student->load(['user', 'class']),
            'children' => auth()->user()->guardian->students()->with('user')->get(),
            'records' => $query->orderByDesc('date')->paginate(15)->withQueryString(),
            'summary' => SchoolHelper::attendanceSummary($student->id, $from, $to),
            'month' => $month,
        ]);
    }

    private function authorizeChild(Student $student): void
    {
        $guardian = auth()->user()->guardian;
        abort_unless($guardian && $student->guardian_id === $guardian->id, 403, 'This student is not linked to your account.');
    }
}
