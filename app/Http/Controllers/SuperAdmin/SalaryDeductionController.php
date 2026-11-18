<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Models\Setting;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryDeductionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Unauthorized. Only Super Admin can manage salary deductions.');

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::with(['user', 'branch', 'teacher.user'])
            ->where('employment_status', 'active')
            ->orderBy('employee_no')
            ->get();

        $deductions = PayrollDeduction::with(['employee.user', 'employee.branch', 'creator', 'payrollEntry.run'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('employee', fn ($emp) => $emp->where('branch_id', $request->integer('branch_id'))))
            ->when($request->filled('month'), fn ($q) => $q->where('payroll_month', $request->input('month')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('updated_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalDeductions = (float) PayrollDeduction::sum('amount');
        $pendingDeductions = (float) PayrollDeduction::where('status', 'pending')->sum('amount');
        $appliedDeductions = (float) PayrollDeduction::where('status', 'applied')->sum('amount');
        $staffCount = PayrollDeduction::distinct('employee_id')->count('employee_id');

        $autoDeductionEnabled = (bool) Setting::get('payroll.auto_deduction_enabled', true);
        $autoDeductionMode = (string) Setting::get('payroll.auto_deduction_mode', 'pro_rata');
        $autoDeductionFixedAmount = (float) Setting::get('payroll.auto_deduction_fixed_amount', 2000.00);
        $autoDeductionLatePenalty = (float) Setting::get('payroll.auto_deduction_late_penalty', 250.00);

        return view('SuperAdmin.SalaryDeductionsIndex', compact(
            'deductions',
            'employees',
            'branches',
            'totalDeductions',
            'pendingDeductions',
            'appliedDeductions',
            'staffCount',
            'autoDeductionEnabled',
            'autoDeductionMode',
            'autoDeductionFixedAmount',
            'autoDeductionLatePenalty'
        ));
    }

    public function updatePolicy(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Unauthorized. Only Super Admin can change deduction policies.');

        $data = $request->validate([
            'auto_deduction_enabled' => ['required', 'boolean'],
            'auto_deduction_mode' => ['required', 'in:pro_rata,fixed_amount'],
            'auto_deduction_fixed_amount' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'auto_deduction_late_penalty' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        Setting::set('payroll.auto_deduction_enabled', (bool) $data['auto_deduction_enabled'], 'payroll');
        Setting::set('payroll.auto_deduction_mode', $data['auto_deduction_mode'], 'payroll');
        Setting::set('payroll.auto_deduction_fixed_amount', (float) $data['auto_deduction_fixed_amount'], 'payroll');
        Setting::set('payroll.auto_deduction_late_penalty', (float) ($data['auto_deduction_late_penalty'] ?? 0), 'payroll');

        return redirect()->route('admin.salary-deductions.index')
            ->with('success', 'Automatic attendance-based deduction policy updated successfully.');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Unauthorized. Only Super Admin can record salary deductions.');

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:advance,penalty,disciplinary,equipment,uniform,manual,leave_unpaid,other'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'payroll_month' => ['required', 'regex:/^\d{4}-(?:0[1-9]|1[0-2])$/'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $deduction = PayrollDeduction::create([
                'employee_id' => $data['employee_id'],
                'payroll_month' => $data['payroll_month'],
                'effective_date' => $data['effective_date'] ?? ($data['payroll_month'].'-01'),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'created_by' => $request->user()->id,
            ]);

            // If there is already a draft payroll entry for this employee in this month, connect and update
            $draftEntry = PayrollEntry::where('employee_id', $data['employee_id'])
                ->whereHas('run', function ($q) use ($data) {
                    $q->where('status', 'draft')
                        ->where('period_start', 'like', $data['payroll_month'].'%');
                })
                ->first();

            if ($draftEntry) {
                $deduction->update([
                    'payroll_entry_id' => $draftEntry->id,
                    'status' => 'applied',
                ]);

                $newManualDeduction = Money::add((string) $draftEntry->manual_deduction, (string) $data['amount']);
                $newTotalDeduction = Money::add((string) $draftEntry->leave_deduction, $newManualDeduction);
                $combinedReasons = trim(($draftEntry->manual_deduction_reason ? $draftEntry->manual_deduction_reason.'; ' : '').$data['reason']);

                $statutory = Money::add((string) $draftEntry->employee_epf, (string) $draftEntry->apit_deduction);
                $grossPay = Money::add((string) $draftEntry->base_salary, (string) $draftEntry->allowance);
                $allDeductions = Money::add($statutory, $newTotalDeduction);
                $netPay = Money::comp($grossPay, $allDeductions) > 0 ? Money::sub($grossPay, $allDeductions) : '0.00';

                $draftEntry->update([
                    'manual_deduction' => $newManualDeduction,
                    'deduction' => $newTotalDeduction,
                    'manual_deduction_reason' => $combinedReasons,
                    'net_pay' => $netPay,
                ]);
            }

            AuditLogger::log('created_salary_deduction', $deduction, null, $deduction->toArray());
        });

        return redirect()->route('admin.salary-deductions.index')
            ->with('success', 'Salary deduction successfully created by Super Admin. Connected staff can now view this in their personal profile.');
    }

    public function destroy(Request $request, PayrollDeduction $salaryDeduction)
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Unauthorized. Only Super Admin can remove salary deductions.');

        DB::transaction(function () use ($salaryDeduction) {
            if ($salaryDeduction->payroll_entry_id && $salaryDeduction->payrollEntry?->run?->status === 'draft') {
                $entry = $salaryDeduction->payrollEntry;
                $newManualDeduction = max(0, (float) $entry->manual_deduction - (float) $salaryDeduction->amount);
                $newTotalDeduction = max(0, (float) $entry->leave_deduction + $newManualDeduction);

                $grossPay = (float) $entry->base_salary + (float) $entry->allowance;
                $statutory = (float) $entry->employee_epf + (float) $entry->apit_deduction;
                $netPay = max(0, $grossPay - ($statutory + $newTotalDeduction));

                $entry->update([
                    'manual_deduction' => sprintf('%.2f', $newManualDeduction),
                    'deduction' => sprintf('%.2f', $newTotalDeduction),
                    'net_pay' => sprintf('%.2f', $netPay),
                ]);
            }

            AuditLogger::log('deleted_salary_deduction', $salaryDeduction, $salaryDeduction->toArray(), null);
            $salaryDeduction->delete();
        });

        return redirect()->route('admin.salary-deductions.index')
            ->with('success', 'Salary deduction removed successfully.');
    }
}
