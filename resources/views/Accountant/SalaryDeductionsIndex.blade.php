@extends('Layouts.LayoutApp')
@section('title', 'Salary Deductions')
@section('content')
<div class="mb-3">
    <h3 class="mb-0">Salary Deductions</h3>
    <small class="text-muted">Read-only deduction history for the selected campus.</small>
</div>
<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label for="employee_id" class="form-label">Staff member</label>
        <select id="employee_id" name="employee_id" class="form-select" data-searchable-select>
            <option value="">All staff</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                    {{ $employee->employee_no }} — {{ $employee->user?->name ?? $employee->teacher?->user?->name ?? $employee->job_title }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label for="month" class="form-label">Payroll month</label><input id="month" type="month" name="month" class="form-control" value="{{ request('month') }}"></div>
    <div class="col-md-3">
        <label for="type" class="form-label">Deduction type</label>
        <select id="type" name="type" class="form-select">
            <option value="">All types</option>
            @foreach (['advance', 'disciplinary', 'penalty', 'leave_unpaid', 'equipment', 'uniform', 'manual', 'other'] as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('accountant.salary-deductions.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-circle"></i></a></div>
</form>
<div class="card shadow-sm border border-secondary-subtle">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Date / Period</th><th>Staff Member</th><th>Campus</th><th>Category</th><th class="text-end">Amount</th><th>Reason</th><th>Status</th><th>Authorized By</th></tr></thead>
            <tbody>
            @forelse ($deductions as $deduction)
                @php($employee = $deduction->employee)
                <tr>
                    <td>{{ $deduction->effective_date?->format('d M Y') ?? '—' }}<div class="small text-muted">{{ $deduction->payroll_month ?: ($deduction->payrollEntry?->run?->period_start?->format('Y-m') ?? '—') }}</div></td>
                    <td><div class="fw-semibold">{{ $employee?->user?->name ?? $employee?->teacher?->user?->name ?? '—' }}</div><small class="text-muted">{{ $employee?->employee_no }}</small></td>
                    <td>{{ $employee?->branch?->name ?? 'Main Campus' }}</td>
                    <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ ucwords(str_replace('_', ' ', $deduction->type)) }}</span></td>
                    <td class="text-end text-danger fw-semibold">−{{ \App\Support\Money::format((string) $deduction->amount, config('school.default_currency', 'LKR').' ') }}</td>
                    <td><div>{{ $deduction->reason }}</div>@if ($deduction->notes)<small class="text-muted">{{ $deduction->notes }}</small>@endif</td>
                    <td>
                        @if ($deduction->status === 'applied')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Applied</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                        @endif
                    </td>
                    <td>{{ $deduction->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No salary deductions found for the selected filters and campus.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deductions->links('pagination::bootstrap-5') }}</div>
@endsection
