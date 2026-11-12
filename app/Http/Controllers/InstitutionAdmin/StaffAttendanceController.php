<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreStaffAttendanceRequest;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('staff_attendance.read'), 403);
        $filters = $request->validate(['date' => ['nullable', 'date', 'before_or_equal:today']]);
        $date = $filters['date'] ?? today()->toDateString();
        $teachers = Teacher::with('user')
            ->whereIn('status', ['active', 'on_leave'])
            ->whereHas('user')
            ->orderBy('id')
            ->get();
        $attendance = StaffAttendance::whereDate('attendance_date', $date)
            ->get()
            ->keyBy('teacher_id');

        return view('InstitutionAdmin.InstitutionAdminStaffAttendanceIndex', compact('date', 'teachers', 'attendance'));
    }

    public function store(StoreStaffAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $teacher = Teacher::with('user')->findOrFail($data['teacher_id']);
        abort_unless(in_array($teacher->status, ['active', 'on_leave'], true), 422, 'Attendance can only be recorded for active staff.');

        DB::transaction(function () use ($data, $teacher) {
            $record = StaffAttendance::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('attendance_date', $data['attendance_date'])
                ->lockForUpdate()
                ->first();

            if ($record) {
                $before = $record->only(['check_in', 'check_out', 'notes']);
                $record->update([
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
                AuditLogger::log('updated_staff_attendance', $record, $before, $record->only(['check_in', 'check_out', 'notes']));

                return;
            }

            $record = StaffAttendance::create([
                'teacher_id' => $teacher->id,
                'staff_name' => $teacher->user->name,
                'attendance_date' => $data['attendance_date'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);
            AuditLogger::log('recorded_staff_attendance', $record, null, $record->toArray());
        });

        return redirect()->route('admin.staff-attendance.index', ['date' => $data['attendance_date']])
            ->with('success', 'Staff attendance saved.');
    }
}
