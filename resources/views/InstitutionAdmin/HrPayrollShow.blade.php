@extends('Layouts.LayoutApp')
@section('title', 'Payroll details')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Payroll details</h3><small class="text-muted">{{ $payrollRun->period_start->format('Y-m-d') }} – {{ $payrollRun->period_end->format('Y-m-d') }} · {{ ucfirst($payrollRun->status) }}</small></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.hr.payroll.index') }}">Back to payroll</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Employee</th><th>Base salary</th><th>Allowance</th><th>Employee EPF</th><th>APIT</th><th>Employer EPF</th><th>Employer ETF</th><th>Other deductions</th><th>Net pay</th>@if($payrollRun->status === 'draft')<th>Update draft</th>@endif</tr></thead>
    <tbody>
    @forelse($payrollRun->entries as $entry)
        <tr><td>{{ $entry->employee->employee_no }} — {{ $entry->employee->user?->name ?? $entry->employee->teacher?->user?->name ?? $entry->employee->job_title }}</td>
            <td>{{ \App\Support\Money::format($entry->base_salary) }}</td>
            @if($payrollRun->status === 'draft')
            <td colspan="8"><form method="POST" action="{{ route('admin.hr.payroll.entries.update', $entry) }}" class="row g-2 align-items-center">
                @csrf @method('PUT')
                <div class="col"><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="allowance" value="{{ $entry->allowance }}" aria-label="Allowance" required></div>
                <div class="col"><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="deduction" value="{{ $entry->deduction }}" aria-label="Deduction" required></div>
                <div class="col"><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="apit_deduction" value="{{ $entry->apit_deduction }}" aria-label="APIT deduction" required></div>
                <div class="col"><input class="form-control form-control-sm" name="notes" value="{{ $entry->notes }}" placeholder="Notes (optional)"></div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                <div class="col-12 small text-muted">EPF employee {{ \App\Support\Money::format($entry->employee_epf) }} · EPF employer {{ \App\Support\Money::format($entry->employer_epf) }} · ETF employer {{ \App\Support\Money::format($entry->employer_etf) }} · APIT {{ \App\Support\Money::format($entry->apit_deduction) }} · Net {{ \App\Support\Money::format($entry->net_pay) }}</div>
            </form></td>
            @else<td>{{ \App\Support\Money::format($entry->allowance) }}</td><td>{{ \App\Support\Money::format($entry->employee_epf) }}</td><td>{{ \App\Support\Money::format($entry->apit_deduction) }}</td><td>{{ \App\Support\Money::format($entry->employer_epf) }}</td><td>{{ \App\Support\Money::format($entry->employer_etf) }}</td><td>{{ \App\Support\Money::format($entry->deduction) }}</td><td>{{ \App\Support\Money::format($entry->net_pay) }}</td>@endif
        </tr>
    @empty<tr><td colspan="10" class="text-center text-muted py-4">No payroll entries.</td></tr>@endforelse
    </tbody>
    @php($totalNet = $payrollRun->entries->reduce(fn ($total, $entry) => \App\Support\Money::add($total, $entry->net_pay), '0.00'))
    <tfoot class="table-light"><tr><th colspan="8" class="text-end">Total net pay</th><th>{{ \App\Support\Money::format($totalNet) }}</th>@if($payrollRun->status === 'draft')<th></th>@endif</tr></tfoot>
</table></div></div>
<div class="d-flex gap-2 mt-3">
    @if($payrollRun->status === 'draft')
        @can('payroll.approve')<form method="POST" action="{{ route('admin.hr.payroll.approve', $payrollRun) }}">@csrf<button class="btn btn-success">Approve payroll</button></form>@endcan
    @elseif($payrollRun->status === 'approved')
        @can('payroll.pay')
            <form method="POST" action="{{ route('admin.hr.payroll.paid', $payrollRun) }}" class="d-flex gap-2">
                @csrf
                <select name="payout_account_id" class="form-select" required aria-label="Payroll payout account">
                    @foreach($payoutAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach
                </select>
                <button class="btn btn-primary" type="submit">Mark paid and post journal</button>
            </form>
        @endcan
    @endif
</div>
<p class="small text-muted mt-3">Sri Lankan EPF/ETF rates and APIT treatment are snapshotted for this draft. Review APIT mode and manual exceptions before approval; overtime and leave proration are not included.</p>
@endsection
