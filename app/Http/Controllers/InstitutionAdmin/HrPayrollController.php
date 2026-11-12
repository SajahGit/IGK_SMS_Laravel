<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\Teacher;
use App\Models\User;
use App\Services\HrPayrollService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HrPayrollController extends Controller
{
    public function employees(Request $request): View
    {
        $this->authorize('hr.read');
        $employees = Employee::query()
            ->with(['user', 'teacher.user', 'department'])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($nested) use ($request) {
                $nested->where('employee_no', 'like', '%'.$request->string('search').'%')
                    ->orWhere('job_title', 'like', '%'.$request->string('search').'%')
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%'))
                    ->orWhereHas('teacher.user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%'));
            }))
            ->orderBy('employee_no')
            ->paginate(15)
            ->withQueryString();

        return view('InstitutionAdmin.HrEmployeesIndex', compact('employees'));
    }

    public function employeeCreate(): View
    {
        $this->authorize('hr.create');
        $employee = null;

        return view('InstitutionAdmin.HrEmployeeForm', $this->employeeFormData($employee));
    }

    public function employeeStore(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $this->validateEmployee($request);
        $employee = Employee::create($data);
        AuditLogger::log('created_employee', $employee, null, $employee->toArray());

        return redirect()->route('admin.hr.employees.index')->with('success', 'Employee created.');
    }

    public function employeeEdit(Employee $employee): View
    {
        $this->authorize('hr.update');

        return view('InstitutionAdmin.HrEmployeeForm', $this->employeeFormData($employee));
    }

    public function employeeUpdate(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('hr.update');
        $data = $this->validateEmployee($request, $employee);
        $before = $employee->toArray();
        $employee->update($data);
        AuditLogger::log('updated_employee', $employee, $before, $employee->fresh()->toArray());

        return redirect()->route('admin.hr.employees.index')->with('success', 'Employee updated.');
    }

    public function employeeDestroy(Employee $employee): RedirectResponse
    {
        $this->authorize('hr.delete');
        if ($employee->leaveRequests()->exists() || $employee->payrollEntries()->exists()) {
            return back()->withErrors(['employee' => 'Employees with leave or payroll history cannot be deleted. Set employment status to terminated instead.']);
        }

        $before = $employee->toArray();
        $employee->delete();
        AuditLogger::log('deleted_employee', $employee, $before, null);

        return redirect()->route('admin.hr.employees.index')->with('success', 'Employee deleted.');
    }

    public function leaves(): View
    {
        $this->authorize('hr.read');
        $leaveRequests = LeaveRequest::query()->with(['employee.user', 'employee.teacher.user', 'reviewer'])
            ->whereHas('employee')
            ->latest('start_date')->paginate(20);
        $employees = Employee::query()->where('employment_status', 'active')->with(['user', 'teacher.user'])->orderBy('employee_no')->get();

        return view('InstitutionAdmin.HrLeavesIndex', compact('leaveRequests', 'employees'));
    }

    public function leaveStore(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('employment_status', 'active')],
            'leave_type' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        HrPayrollService::requestLeave($data, (int) $request->user()->id);

        return redirect()->route('admin.hr.leaves.index')->with('success', 'Leave request submitted for review.');
    }

    public function leaveReview(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('hr.approve');
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);
        HrPayrollService::reviewLeave($leaveRequest, $data['decision'], $data['review_note'] ?? null, (int) $request->user()->id);

        return redirect()->route('admin.hr.leaves.index')->with('success', 'Leave request reviewed.');
    }

    public function payrollIndex(): View
    {
        $this->authorize('payroll.read');
        $runs = PayrollRun::query()->withCount('entries')->latest('period_start')->paginate(15);

        return view('InstitutionAdmin.HrPayrollIndex', compact('runs'));
    }

    public function payrollGenerate(Request $request): RedirectResponse
    {
        $this->authorize('payroll.create');
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $run = HrPayrollService::generatePayroll($data['period_start'], $data['period_end'], (int) $request->user()->id);

        return redirect()->route('admin.hr.payroll.show', $run)->with('success', 'Draft payroll generated from active employees.');
    }

    public function payrollShow(PayrollRun $payrollRun): View
    {
        $this->authorize('payroll.read');
        $payrollRun->load(['entries.employee.user', 'entries.employee.teacher.user', 'generator', 'approver']);
        $payoutAccounts = Account::query()->whereIn('code', ['1000', '1010'])->where('is_active', true)->orderBy('code')->get();

        return view('InstitutionAdmin.HrPayrollShow', compact('payrollRun', 'payoutAccounts'));
    }

    public function payrollEntryUpdate(Request $request, PayrollEntry $payrollEntry): RedirectResponse
    {
        $this->authorize('payroll.update');
        $data = $request->validate([
            'allowance' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'deduction' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'apit_deduction' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        HrPayrollService::updateEntry(
            $payrollEntry,
            $data['allowance'],
            $data['deduction'],
            $data['notes'] ?? null,
            $data['apit_deduction'] ?? null
        );

        return redirect()->route('admin.hr.payroll.show', $payrollEntry->payroll_run_id)->with('success', 'Payroll entry updated.');
    }

    public function payrollApprove(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('payroll.approve');
        HrPayrollService::approvePayroll($payrollRun, (int) $request->user()->id);

        return redirect()->route('admin.hr.payroll.show', $payrollRun)->with('success', 'Payroll approved and locked.');
    }

    public function payrollPaid(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('payroll.pay');
        $data = $request->validate(['payout_account_id' => ['required', 'exists:accounts,id']]);
        HrPayrollService::markPaid($payrollRun, (int) $request->user()->id, (int) $data['payout_account_id']);

        return redirect()->route('admin.hr.payroll.show', $payrollRun)->with('success', 'Payroll marked as paid.');
    }

    private function employeeFormData(?Employee $employee): array
    {
        $employeesWithUsers = Employee::query()->pluck('user_id')->filter();
        $teachersWithIds = Employee::query()->pluck('teacher_id')->filter();
        $users = User::query()->whereIn('role', ['teacher', 'registrar', 'accountant', 'librarian', 'staff'])
            ->where(function ($query) use ($employeesWithUsers, $employee) {
                $query->whereNotIn('id', $employeesWithUsers)
                    ->when($employee?->user_id, fn ($nested, $id) => $nested->orWhereKey($id));
            })
            ->orderBy('name')->get();
        $teachers = Teacher::query()->with('user')
            ->where(function ($query) use ($teachersWithIds, $employee) {
                $query->whereNotIn('id', $teachersWithIds)
                    ->when($employee?->teacher_id, fn ($nested, $id) => $nested->orWhereKey($id));
            })
            ->orderBy('id')->get();
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();

        return compact('employee', 'users', 'teachers', 'departments', 'branches');
    }

    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('employees', 'employee_no')->ignore($employee)],
            'user_id' => [
                'nullable',
                'integer',
                Rule::unique('employees', 'user_id')->ignore($employee),
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['teacher', 'registrar', 'accountant', 'librarian', 'staff'])),
            ],
            'teacher_id' => ['nullable', 'integer', Rule::unique('employees', 'teacher_id')->ignore($employee), 'exists:teachers,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'job_title' => ['required', 'string', 'max:120'],
            'hire_date' => ['required', 'date'],
            'employment_status' => ['required', Rule::in(['active', 'on_leave', 'terminated'])],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'apit_mode' => ['required', Rule::in(['primary', 'secondary', 'exempt', 'manual'])],
            'branch_id' => ['nullable', Rule::requiredIf(Branch::query()->where('is_active', true)->exists()), 'exists:branches,id'],
        ]);

        if (! empty($data['user_id']) && ! empty($data['teacher_id'])
            && (int) Teacher::whereKey($data['teacher_id'])->value('user_id') !== (int) $data['user_id']) {
            throw ValidationException::withMessages(['teacher_id' => 'The linked account must match the selected teacher profile.']);
        }

        return $data;
    }
}
