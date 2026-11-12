<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
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
                $statutory = app(SriLankaPayrollCalculator::class)->calculate(
                    (string) $employee->base_salary,
                    '0.00',
                    $employee->apit_mode,
                    $periodStart
                );
                PayrollEntry::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'base_salary' => $employee->base_salary,
                    'allowance' => '0.00',
                    'deduction' => '0.00',
                    'employee_epf' => $statutory['employee_epf'],
                    'employer_epf' => $statutory['employer_epf'],
                    'employer_etf' => $statutory['employer_etf'],
                    'apit_deduction' => $statutory['apit_deduction'],
                    'net_pay' => Money::sub(
                        Money::sub($statutory['gross_pay'], $statutory['employee_epf']),
                        $statutory['apit_deduction']
                    ),
                ]);
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
        ?string $apitDeduction = null
    ): void {
        if (! Money::hasValidScale($allowance) || ! Money::hasValidScale($deduction)
            || ($apitDeduction !== null && ! Money::hasValidScale($apitDeduction))) {
            throw ValidationException::withMessages(['allowance' => 'Payroll amounts must be valid with up to two decimal places.']);
        }

        DB::transaction(function () use ($entry, $allowance, $deduction, $notes, $apitDeduction) {
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
            $apit = $apitDeduction ?? $statutory['apit_deduction'];
            $totalDeductions = Money::add(Money::add($statutory['employee_epf'], $apit), $deduction);
            if (Money::comp($totalDeductions, $statutory['gross_pay']) > 0) {
                throw ValidationException::withMessages(['deduction' => 'Total employee EPF, APIT and other deductions cannot exceed gross pay.']);
            }

            $before = $locked->toArray();
            $locked->update([
                'allowance' => $allowance,
                'deduction' => $deduction,
                'employee_epf' => $statutory['employee_epf'],
                'employer_epf' => $statutory['employer_epf'],
                'employer_etf' => $statutory['employer_etf'],
                'apit_deduction' => $apit,
                'net_pay' => Money::sub($statutory['gross_pay'], $totalDeductions),
                'notes' => $notes,
            ]);
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
