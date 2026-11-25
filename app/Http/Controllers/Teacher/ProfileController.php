<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Teacher or Employee viewing their own profile, campus assignments, salary, deductions, leaves, and personal attendance.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $teacher = Teacher::with([
            'user',
            'branch',
            'department',
            'branchTransfers.fromBranch',
            'branchTransfers.toBranch',
            'branchTransfers.actor',
            'classSubjects.schoolClass',
            'classSubjects.subject',
        ])->where('user_id', $user->id)->first();

        $employee = Employee::withoutGlobalScopes()
            ->with(['branch', 'department', 'user'])
            ->where(function ($q) use ($user, $teacher) {
                $q->where('user_id', $user->id);
                if ($teacher) {
                    $q->orWhere('teacher_id', $teacher->id);
                }
            })
            ->first();

        if (! $teacher && ! $employee) {
            if ($user->isSuperAdmin() || $user->hasRole(['admin', 'institution_admin'])) {
                $teacher = Teacher::first();
                $employee = Employee::first();
            }
            abort_unless($teacher || $employee, 404, 'No teacher or staff profile linked to your account.');
        }

        return $this->renderProfileView($teacher, true, $employee);
    }

    /**
     * Dedicated self-service endpoint for employees and teachers to view their personal attendance details.
     */
    public function myAttendance(Request $request): View
    {
        return $this->show($request);
    }

    /**
     * Shared render method for teacher self-service, employee self-service, and admin viewing staff.
     */
    public function renderProfileView(?Teacher $teacher, bool $isSelf = false, ?Employee $employee = null): View
    {
        if ($teacher) {
            $teacher->loadMissing([
                'user',
                'branch',
                'department',
                'branchTransfers.fromBranch',
                'branchTransfers.toBranch',
                'branchTransfers.actor',
                'classSubjects.schoolClass',
                'classSubjects.subject',
            ]);
        }

        if (! $employee && $teacher) {
            $employee = Employee::withoutGlobalScopes()
                ->with(['branch', 'department', 'user'])
                ->where(function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)
                        ->orWhere('user_id', $teacher->user_id);
                })
                ->first();
        }

        if ($employee) {
            $employee->loadMissing(['user', 'branch', 'department']);
        }

        $user = $teacher?->user ?? $employee?->user ?? auth()->user();

        $payrollEntries = collect();
        $payrollDeductions = collect();
        $leaveRequests = collect();

        if ($employee) {
            $payrollEntries = PayrollEntry::with(['run', 'deductions'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->take(12)
                ->get();

            $payrollDeductions = PayrollDeduction::with(['creator', 'payrollEntry.run'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->get();

            $leaveRequests = $employee->leaveRequests()
                ->latest('start_date')
                ->take(20)
                ->get();
        }

        $attendances = StaffAttendance::query()
            ->where(function ($q) use ($teacher, $employee, $user) {
                if ($teacher) {
                    $q->where('teacher_id', $teacher->id);
                }
                if ($employee) {
                    $q->orWhere('employee_id', $employee->id);
                }
                if ($user) {
                    $q->orWhere('staff_name', $user->name);
                }
            })
            ->latest('attendance_date')
            ->take(60)
            ->get();

        $currentMonthAttendances = $attendances->filter(function ($att) {
            return $att->attendance_date && \Carbon\Carbon::parse($att->attendance_date)->isCurrentMonth();
        });

        $attendanceStats = [
            'days_present' => $currentMonthAttendances->whereNotNull('check_in')->count(),
            'total_hours' => round($currentMonthAttendances->sum('working_hours'), 1),
            'avg_daily_hours' => $currentMonthAttendances->count() > 0
                ? round($currentMonthAttendances->sum('working_hours') / $currentMonthAttendances->count(), 1)
                : 0.0,
            'on_time_count' => $currentMonthAttendances->filter(fn ($att) => $att->status === 'present')->count(),
            'late_count' => $currentMonthAttendances->filter(fn ($att) => $att->status === 'late')->count(),
        ];

        $unpaidLeaveCount = $leaveRequests->filter(function ($leave) {
            return in_array($leave->leave_type, ['unpaid', 'unpaid_leave', 'loss_of_pay', 'lop']) && $leave->status === 'approved';
        })->sum(function ($leave) {
            return $leave->start_date && $leave->end_date ? ($leave->start_date->diffInDays($leave->end_date) + 1) : 1;
        });

        return view('Teacher.TeacherProfile', compact(
            'teacher',
            'employee',
            'payrollEntries',
            'payrollDeductions',
            'leaveRequests',
            'attendances',
            'attendanceStats',
            'unpaidLeaveCount',
            'isSelf'
        ));
    }

    /**
     * Teacher viewing their own individual payslip.
     */
    public function payslip(Request $request, PayrollEntry $payrollEntry): View
    {
        $user = $request->user();
        $isOwner = $user && (
            $user->id === $payrollEntry->employee?->user_id
            || $user->id === $payrollEntry->employee?->teacher?->user_id
        );

        abort_unless($isOwner || $user->isSuperAdmin() || $user->can('payroll.read'), 403, 'Unauthorized to view this payslip.');

        $payrollEntry->load([
            'run',
            'employee.user',
            'employee.teacher.user',
            'employee.department',
            'employee.branch',
            'deductions',
        ]);

        return view('InstitutionAdmin.HrPayrollPayslip', compact('payrollEntry'));
    }
}
