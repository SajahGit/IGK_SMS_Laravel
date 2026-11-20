@extends('Layouts.LayoutApp')
@section('title', 'Payroll Run')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Payroll Run</h3>
        <small class="text-muted">{{ $payrollRun->period_start->format('d M Y') }} – {{ $payrollRun->period_end->format('d M Y') }}</small>
    </div>
    <a href="{{ route('accountant.payroll.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Payroll</a>
</div>
<div class="d-flex gap-2 mb-3">
    <span class="badge bg-info-subtle text-info border border-info-subtle">Status: {{ ucfirst($payrollRun->status) }}</span>
    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">{{ $payrollRun->entries->count() }} employees</span>
</div>
<div class="card shadow-sm border border-secondary-subtle">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Employee</th><th>Campus</th><th class="text-end">Base Salary</th><th class="text-end">Allowance</th><th class="text-end">Deductions</th><th class="text-end">Net Pay</th></tr></thead>
            <tbody>
            @forelse ($payrollRun->entries as $entry)
                @php($employee = $entry->employee)
                <tr>
                    <td><div class="fw-semibold">{{ $employee?->user?->name ?? $employee?->teacher?->user?->name ?? '—' }}</div><small class="text-muted">{{ $employee?->employee_no }}</small></td>
                    <td>{{ $employee?->branch?->name ?? 'Main Campus' }}</td>
                    <td class="text-end">{{ \App\Support\Money::format((string) $entry->base_salary, config('school.default_currency', 'LKR').' ') }}</td>
                    <td class="text-end">{{ \App\Support\Money::format((string) $entry->allowance, config('school.default_currency', 'LKR').' ') }}</td>
                    <td class="text-end text-danger">{{ \App\Support\Money::format((string) $entry->deduction, config('school.default_currency', 'LKR').' ') }}</td>
                    <td class="text-end fw-semibold">{{ \App\Support\Money::format((string) $entry->net_pay, config('school.default_currency', 'LKR').' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No payroll entries found for the selected campus.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
