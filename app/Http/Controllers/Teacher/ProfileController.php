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
     * Teacher viewing their own profile, campus assignments, salary, deductions, leaves, and attendance.
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

        if (! $teacher) {
            if ($user->isSuperAdmin() || $user->hasRole(['admin', 'institution_admin'])) {
                $teacher = Teacher::first();
            }
            abort_unless($teacher, 404, 'No teacher profile linked to your account.');
        }

        return $this->renderProfileView($teacher, true);
    }

    /**
     * Shared render method for both teacher self-service and admin viewing teacher.
     */
    public function renderProfileView(Teacher $teacher, bool $isSelf = false): View
    {
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

        $employee = Employee::withoutGlobalScopes()
            ->with(['branch', 'department'])
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhere('user_id', $teacher->user_id);
            })
            ->first();

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

        $attendances = StaffAttendance::where('teacher_id', $teacher->id)
            ->latest('attendance_date')
            ->take(30)
            ->get();

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
