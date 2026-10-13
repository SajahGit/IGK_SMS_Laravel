<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    /** Save the whole mark sheet of one session. */
    public function update(Request $request, AttendanceSession $session)
    {
        abort_unless($request->user()->teachesClassSubject($session->class_subject_id), 403, 'This is not your class.');

        $statuses = config('school.attendance_statuses');

        $request->validate([
            'status' => ['required', 'array'],
            'status.*' => ['required', 'in:'.implode(',', $statuses)],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:255'],
        ]);

        $students = $session->classSubject->section->students()->where('status', 'active')->get();

        DB::transaction(function () use ($request, $session, $students) {
            foreach ($students as $student) {
                AttendanceRecord::updateOrCreate(
                    ['attendance_session_id' => $session->id, 'student_id' => $student->id],
                    [
                        'status' => $request->input("status.{$student->id}", 'present'),
                        'remarks' => $request->input("remarks.{$student->id}"),
                    ]
                );
            }

            $session->update(['status' => 'submitted']);
        });

        return redirect()->route('teacher.attendance.show', $session)->with('success', 'Attendance saved.');
    }
}
