<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Support\AuditLogger;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrPayrollService
{
    public static function requestLeave(array $data, int $actorId): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            Employee::query()->lockForUpdate()->findOrFail($data['employee_id']);
            $overlap = LeaveRequest::query()
                ->where('employee_id', $data['employee_id'])
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $data['end_date'])
                ->whereDate('end_date', '>=', $data['start_date'])
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_date' => 'This leave request overlaps an existing pending or approved request.',
                ]);
            }

            $days = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
            $leave = LeaveRequest::create($data + [
                'days' => $days,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            AuditLogger::log('created_leave_request', $leave, null, $leave->toArray());

            return $leave;
        });
    }

    public static function reviewLeave(LeaveRequest $leave, string $decision, ?string $note, int $reviewerId): void
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Choose approve or reject.']);
        }

        DB::transaction(function () use ($leave, $decision, $note, $reviewerId) {
            $locked = LeaveRequest::query()->lockForUpdate()->findOrFail($leave->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['leave' => 'Only pending leave requests can be reviewed.']);
            }
            $before = $locked->toArray();
            $locked->update([
                'status' => $decision,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
            AuditLogger::log('reviewed_leave_request', $locked, $before, $locked->fresh()->toArray());
        });
    }

    public static function generatePayroll(string $periodStart, string $periodEnd, int $actorId): PayrollRun
    {
        if ($periodStart > $periodEnd) {
            throw ValidationException::withMessages(['period_end' => 'Period end must be on or after period start.']);
        }
        $start = Carbon::parse($periodStart);
        $end = Carbon::parse($periodEnd);
        if ($start->copy()->startOfMonth()->toDateString() !== $periodStart
            || $end->copy()->endOfMonth()->toDateString() !== $periodEnd
            || $start->format('Y-m') !== $end->format('Y-m')) {
            throw ValidationException::withMessages(['period_end' => 'Payroll periods must cover one complete calendar month.']);
        }

        return DB::transaction(function () use ($periodStart, $periodEnd, $actorId) {
            $overlap = PayrollRun::query()
                ->whereIn('status', ['draft', 'approved', 'paid'])
                ->whereDate('period_start', '<=', $periodEnd)
                ->whereDate('period_end', '>=', $periodStart)
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages([
                    'period_start' => 'A payroll run already exists for an overlapping period.',
                ]);
            }

            $employees = Employee::query()->where('employment_status', 'active')->orderBy('id')->lockForUpdate()->get();
            if ($employees->isEmpty()) {
                throw ValidationException::withMessages(['payroll' => 'No active employees are available for payroll.']);
            }

            $run = PayrollRun::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'draft',
                'generated_by' => $actorId,
            ]);
            foreach ($employees as $employee) {
                // Calculate approved unpaid leave days in the period
                $autoDeductionEnabled = (bool) Setting::get('payroll.auto_deduction_enabled', true);
                $autoDeductionMode = (string) Setting::get('payroll.auto_deduction_mode', 'pro_rata');
                $fixedDailyRate = (float) Setting::get('payroll.auto_deduction_fixed_amount', 2000.00);

                $unpaidDays = 0;
                $leaveDeduction = 0.00;
                $leaveDeductionStr = '0.00';
                $dailyRate = 0.00;

                if ($autoDeductionEnabled) {
                    $unpaidLeaves = LeaveRequest::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->whereIn('leave_type', ['unpaid', 'unpaid_leave', 'loss_of_pay', 'lop'])
                        ->whereDate('start_date', '<=', $periodEnd)
                        ->whereDate('end_date', '>=', $periodStart)
                        ->get();

                    foreach ($unpaidLeaves as $leave) {
                        $leaveStart = Carbon::parse(max($leave->start_date->toDateString(), $periodStart));
                        $leaveEnd = Carbon::parse(min($leave->end_date->toDateString(), $periodEnd));
                        $days = $leaveStart->diffInDays($leaveEnd) + 1;
                        $unpaidDays += (int) $days;
                    }

                    if ($employee->teacher_id) {
                        $absentAttendanceCount = StaffAttendance::query()
                            ->where('teacher_id', $employee->teacher_id)
                            ->whereDate('attendance_date', '>=', $periodStart)
                            ->whereDate('attendance_date', '<=', $periodEnd)
                            ->where('notes', 'like', '%absent%')
                            ->count();
                        if ($absentAttendanceCount > 0 && $unpaidDays === 0) {
                            $unpaidDays += $absentAttendanceCount;
                        }
                    }

                    if ($autoDeductionMode === 'fixed_amount') {
                        $dailyRate = $fixedDailyRate;
                    } else {
                        $dailyRate = round(((float) $employee->base_salary) / 30, 2);
                    }

                    $leaveDeduction = round($dailyRate * $unpaidDays, 2);
                    $leaveDeductionStr = sprintf('%.2f', $leaveDeduction);
                }

                $statutory = app(SriLankaPayrollCalculator::class)->calculate(
                    (string) $employee->base_salary,
                    '0.00',
                    $employee->apit_mode,
                    $periodStart
                );

                $periodMonth = Carbon::parse($periodStart)->format('Y-m');
                $pendingDeductions = PayrollDeduction::where('employee_id', $employee->id)
                    ->where(function ($q) use ($periodMonth, $periodStart, $periodEnd) {
                        $q->where('payroll_month', $periodMonth)
                            ->orWhere(function ($sub) use ($periodStart, $periodEnd) {
                                $sub->whereNotNull('effective_date')
                                    ->whereBetween('effective_date', [$periodStart, $periodEnd]);
                            });
                    })
                    ->where('status', 'pending')
                    ->get();

                $manualDeductionTotal = (float) $pendingDeductions->sum('amount');
                $manualDeductionStr = sprintf('%.2f', $manualDeductionTotal);
                $manualReasons = $pendingDeductions->pluck('reason')->filter()->implode('; ');

                $allDeductions = Money::add(
                    Money::add($statutory['employee_epf'], $statutory['apit_deduction']),
                    Money::add($leaveDeductionStr, $manualDeductionStr)
                );

                $netPay = Money::comp($statutory['gross_pay'], $allDeductions) > 0
                    ? Money::sub($statutory['gross_pay'], $allDeductions)
                    : '0.00';

                $entry = PayrollEntry::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'base_salary' => $employee->base_salary,
                    'allowance' => '0.00',
                    'deduction' => Money::add($leaveDeductionStr, $manualDeductionStr),
                    'unpaid_leave_days' => $unpaidDays,
                    'leave_deduction' => $leaveDeductionStr,
                    'manual_deduction' => $manualDeductionStr,
                    'manual_deduction_reason' => $manualReasons ?: null,
                    'employee_epf' => $statutory['employee_epf'],
                    'employer_epf' => $statutory['employer_epf'],
                    'employer_etf' => $statutory['employer_etf'],
                    'apit_deduction' => $statutory['apit_deduction'],
                    'net_pay' => $netPay,
                ]);

                foreach ($pendingDeductions as $pDed) {
                    $pDed->update([
                        'payroll_entry_id' => $entry->id,
                        'status' => 'applied',
                    ]);
                }

                if ($unpaidDays > 0 && $leaveDeduction > 0) {
                    $rateDescription = $autoDeductionMode === 'fixed_amount'
                        ? 'Super Admin configured fixed rate of Rs. '.number_format($dailyRate, 2).' / day'
                        : 'pro-rata rate of Rs. '.number_format($dailyRate, 2).' / day (monthly base salary Rs. '.number_format((float) $employee->base_salary, 2).')';

                    PayrollDeduction::create([
                        'payroll_entry_id' => $entry->id,
                        'employee_id' => $employee->id,
                        'type' => 'leave_unpaid',
                        'amount' => $leaveDeduction,
                        'days' => $unpaidDays,
                        'reason' => "Automatic attendance deduction ({$unpaidDays} unpaid day".($unpaidDays > 1 ? 's' : '').')',
                        'notes' => "Calculated automatically from attendance/unpaid leaves at {$rateDescription}",
                        'payroll_month' => $periodMonth,
                        'effective_date' => $periodEnd,
                        'status' => 'applied',
                        'created_by' => $actorId,
                    ]);
                }
            }
            AuditLogger::log('generated_payroll', $run, null, [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'employee_count' => $employees->count(),
            ]);

            return $run;
        });
    }

    public static function updateEntry(
        PayrollEntry $entry,
        string $allowance,
        string $deduction,
        ?string $notes,
        ?string $apitDeduction = null,
        ?string $manualDeduction = null,
        ?string $manualDeductionReason = null,
        ?int $actorId = null
    ): void {
        if (! Money::hasValidScale($allowance) || ! Money::hasValidScale($deduction)
            || ($apitDeduction !== null && ! Money::hasValidScale($apitDeduction))
            || ($manualDeduction !== null && ! Money::hasValidScale($manualDeduction))) {
            throw ValidationException::withMessages(['allowance' => 'Payroll amounts must be valid with up to two decimal places.']);
        }

        DB::transaction(function () use ($entry, $allowance, $deduction, $notes, $apitDeduction, $manualDeduction, $manualDeductionReason, $actorId) {
            $locked = PayrollEntry::query()->lockForUpdate()->with(['run', 'employee'])->findOrFail($entry->id);
            if ($locked->run->status !== 'draft') {
                throw ValidationException::withMessages(['payroll' => 'Only draft payroll entries can be changed.']);
            }

            $statutory = app(SriLankaPayrollCalculator::class)->calculate(
                (string) $locked->base_salary,
                $allowance,
                $locked->employee->apit_mode,
                $locked->run->period_start->toDateString()
            );

            // Compute total deduction
            $finalManualDeduction = $manualDeduction !== null
                ? sprintf('%.2f', (float) $manualDeduction)
                : (string) $locked->manual_deduction;

            $computedDeduction = $manualDeduction !== null
                ? Money::add((string) $locked->leave_deduction, $finalManualDeduction)
                : $deduction;

            $apit = $apitDeduction ?? $statutory['apit_deduction'];
            $totalDeductions = Money::add(Money::add($statutory['employee_epf'], $apit), $computedDeduction);
            if (Money::comp($totalDeductions, $statutory['gross_pay']) > 0) {
                throw ValidationException::withMessages(['deduction' => 'Total employee EPF, APIT and other deductions cannot exceed gross pay.']);
            }

            $before = $locked->toArray();
            $locked->update([
                'allowance' => $allowance,
                'deduction' => $computedDeduction,
                'manual_deduction' => $finalManualDeduction,
                'manual_deduction_reason' => $manualDeductionReason ?? $locked->manual_deduction_reason,
                'employee_epf' => $statutory['employee_epf'],
                'employer_epf' => $statutory['employer_epf'],
                'employer_etf' => $statutory['employer_etf'],
                'apit_deduction' => $apit,
                'net_pay' => Money::sub($statutory['gross_pay'], $totalDeductions),
                'notes' => $notes,
            ]);

            if ($manualDeduction !== null && (float) $manualDeduction > 0) {
                PayrollDeduction::create([
                    'payroll_entry_id' => $locked->id,
                    'employee_id' => $locked->employee_id,
                    'type' => 'manual',
                    'amount' => (float) $finalManualDeduction,
                    'days' => 0,
                    'reason' => $manualDeductionReason ?: 'Manual administrative deduction',
                    'notes' => $notes,
                    'created_by' => $actorId,
                ]);
            }

            AuditLogger::log('updated_payroll_entry', $locked, $before, $locked->fresh()->toArray());
        });
    }

    public static function approvePayroll(PayrollRun $run, int $approverId): void
    {
        DB::transaction(function () use ($run, $approverId) {
            $locked = PayrollRun::query()->lockForUpdate()->withCount('entries')->findOrFail($run->id);
            if ($locked->status !== 'draft' || $locked->entries_count === 0) {
                throw ValidationException::withMessages(['payroll' => 'Only a non-empty draft payroll can be approved.']);
            }
            if ($locked->generated_by === $approverId) {
                throw ValidationException::withMessages(['payroll' => 'The payroll creator cannot approve the same payroll run.']);
            }

            $before = $locked->toArray();
            $locked->update(['status' => 'approved', 'approved_by' => $approverId, 'approved_at' => now()]);
            AuditLogger::log('approved_payroll', $locked, $before, $locked->fresh()->toArray());
        });
    }

    public static function markPaid(PayrollRun $run, int $actorId, int $payoutAccountId): void
    {
        DB::transaction(function () use ($run, $actorId, $payoutAccountId) {
            $locked = PayrollRun::query()->lockForUpdate()->with('entries')->findOrFail($run->id);
            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['payroll' => 'Only approved payroll can be marked paid.']);
            }

            $payoutAccount = Account::query()->where('is_active', true)->findOrFail($payoutAccountId);
            if ($payoutAccount->type !== 'asset' || ! in_array($payoutAccount->code, ['1000', '1010'], true)) {
                throw ValidationException::withMessages(['payout_account_id' => 'Payroll must be paid from Cash in Hand or Bank Account.']);
            }

            $totals = [
                'gross' => '0.00',
                'employee_epf' => '0.00',
                'employer_epf' => '0.00',
                'employer_etf' => '0.00',
                'apit' => '0.00',
                'other_deduction' => '0.00',
                'net' => '0.00',
            ];
            foreach ($locked->entries as $entry) {
                $totals['gross'] = Money::add($totals['gross'], Money::add($entry->base_salary, $entry->allowance));
                foreach ([
                    'employee_epf' => 'employee_epf',
                    'employer_epf' => 'employer_epf',
                    'employer_etf' => 'employer_etf',
                    'apit_deduction' => 'apit',
                    'deduction' => 'other_deduction',
                    'net_pay' => 'net',
                ] as $field => $totalField) {
                    $totals[$totalField] = Money::add($totals[$totalField], $entry->{$field});
                }
            }

            $accountIds = Account::query()->where('is_active', true)
                ->whereIn('code', ['5000', '5010', '5020', '2110', '2120', '2130', '2140'])
                ->pluck('id', 'code');
            $requiredCodes = ['5000', '5010', '5020', '2110', '2120', '2130', '2140'];
            if (collect($requiredCodes)->contains(fn (string $code) => ! isset($accountIds[$code]))) {
                throw ValidationException::withMessages(['payroll' => 'Sri Lankan payroll ledger accounts are missing. Run the accounting chart seeder before marking payroll paid.']);
            }

            $lines = [
                ['account_id' => $accountIds['5000'], 'debit' => $totals['gross'], 'memo' => 'Gross salaries and wages'],
                ['account_id' => $accountIds['5010'], 'debit' => $totals['employer_epf'], 'memo' => 'Employer EPF contribution'],
                ['account_id' => $accountIds['5020'], 'debit' => $totals['employer_etf'], 'memo' => 'Employer ETF contribution'],
                ['account_id' => $payoutAccount->id, 'credit' => $totals['net'], 'memo' => 'Net payroll paid'],
                ['account_id' => $accountIds['2110'], 'credit' => Money::add($totals['employee_epf'], $totals['employer_epf']), 'memo' => 'Employee and employer EPF payable'],
                ['account_id' => $accountIds['2120'], 'credit' => $totals['employer_etf'], 'memo' => 'Employer ETF payable'],
                ['account_id' => $accountIds['2130'], 'credit' => $totals['apit'], 'memo' => 'APIT payable'],
                ['account_id' => $accountIds['2140'], 'credit' => $totals['other_deduction'], 'memo' => 'Other payroll deductions payable'],
            ];
            $lines = array_values(array_filter($lines, fn (array $line) => Money::comp($line['debit'] ?? $line['credit'] ?? '0.00', '0.00') > 0));
            try {
                $journal = JournalService::post([
                    'entry_date' => today()->toDateString(),
                    'description' => 'Payroll '.$locked->period_start->format('Y-m'),
                    'reference' => 'PAYROLL-'.$locked->id,
                ], $lines, 'payroll', $locked->id);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['payroll' => $exception->getMessage()]);
            }

            $before = $locked->toArray();
            $locked->update([
                'status' => 'paid',
                'paid_by' => $actorId,
                'paid_at' => now(),
                'payout_account_id' => $payoutAccount->id,
            ]);
            AuditLogger::log('posted_payroll_journal', $journal, null, ['payroll_run_id' => $locked->id, 'totals' => $totals]);
            AuditLogger::log('paid_payroll', $locked, $before, $locked->fresh()->toArray());
        });
    }
}
