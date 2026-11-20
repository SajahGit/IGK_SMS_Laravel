<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollDeduction;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryDeductionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAccountant(), 403);
        $filters = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', 'in:advance,disciplinary,penalty,leave_unpaid,equipment,uniform,manual,other'],
        ]);
        $branchId = BranchContext::activeBranchId($request->user());
        $deductions = PayrollDeduction::query()
            ->with(['employee.user', 'employee.teacher.user', 'employee.branch', 'creator:id,name', 'payrollEntry.run'])
            ->when($branchId !== null, fn (Builder $query) => $query->whereExists(fn ($employees) => $employees
                ->selectRaw('1')
                ->from('employees')
                ->whereColumn('employees.id', 'payroll_deductions.employee_id')
                ->where('employees.branch_id', $branchId)))
            ->when(isset($filters['employee_id']), fn (Builder $query) => $query->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['month']), fn (Builder $query) => $query->where('payroll_month', $filters['month']))
            ->when(isset($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
        $employees = Employee::query()
            ->with(['user', 'teacher.user'])
            ->orderBy('employee_no')
            ->get();

        return view('Accountant.SalaryDeductionsIndex', compact('deductions', 'employees'));
    }
}
