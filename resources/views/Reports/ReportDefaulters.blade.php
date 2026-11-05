@extends('Layouts.LayoutApp')
@section('title', 'Fee Defaulters Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Fee Defaulters & Outstanding Balances</h3>
        <small class="text-muted">R10 · Term/program/section, overdue-only and minimum-balance filters.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route($reportRoutePrefix.'.defaulters.csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'people-fill',
            'value' => $totalDefaulters,
            'label' => 'Total Students with Due Balances',
            'tone' => 'warning',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'exclamation-octagon-fill',
            'value' => \App\Support\Money::format($totalBalance),
            'label' => 'Total Outstanding Balance',
            'tone' => 'danger',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'receipt',
            'value' => $invoices->count(),
            'label' => 'Pending Invoice Count',
            'tone' => 'primary',
        ])
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($reportRoutePrefix.'.defaulters') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="filter_term" class="form-label">Term</label>
                <select name="term_id" id="filter_term" class="form-select">
                    <option value="">All terms</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" @selected(($filters['term_id'] ?? null) == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_program" class="form-label">Program</label>
                <select name="program_id" id="filter_program" class="form-select">
                    <option value="">All programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_class" class="form-label">Class/Section</label>
                <select name="class_id" id="filter_class" class="form-select">
                    <option value="">All sections</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(($filters['class_id'] ?? null) == $class->id)>{{ $class->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="overdue_days" class="form-label">Minimum days overdue (optional)</label>
                <input type="number" min="0" name="overdue_days" id="overdue_days" class="form-control" value="{{ $filters['overdue_days'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="min_balance" class="form-label">Minimum balance</label>
                <input type="number" min="0" step="0.01" name="min_balance" id="min_balance" class="form-control" value="{{ $filters['min_balance'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="overdue_only" id="overdue_only" value="1" @checked(request()->boolean('overdue_only'))>
                    <label class="form-check-label" for="overdue_only">Overdue only</label>
                </div>
                <button class="btn btn-primary w-100 mt-2">Apply filters</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Invoice No</th>
                    <th>Term</th>
                    <th>Program</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Guardian / Contact</th>
                    <th>Due Date</th>
                    <th>Total Billed</th>
                    <th>Amount Paid</th>
                    <th>Balance Due</th>
                    <th>Days Overdue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    @php
                        $daysOverdue = today()->gt($inv->due_date) ? abs((int) today()->diffInDays($inv->due_date)) : 0;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route($financeRoutePrefix.'.invoices.show', $inv) }}" class="fw-bold">
                                <code>{{ $inv->invoice_no }}</code>
                            </a>
                        </td>
                        <td>{{ $inv->term?->name ?? '—' }}</td>
                        <td>{{ $inv->student->program?->name ?? $inv->student->class?->program?->name ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->user->name }}</div>
                            <small class="text-muted"><code>{{ $inv->student->admission_no }}</code></small>
                        </td>
                        <td>{{ $inv->student->class?->full_name ?? '—' }}</td>
                        <td>
                            @php $primaryGuardian = $inv->student->guardians->firstWhere('pivot.is_primary', true) ?? $inv->student->guardians->first(); @endphp
                            <div>{{ $primaryGuardian?->user?->name ?: '—' }}</div>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $primaryGuardian?->phone ?: 'No phone' }}</small>
                        </td>
                        <td>{{ $inv->due_date->format('d M Y') }}</td>
                        <td>{{ \App\Support\Money::format($inv->total) }}</td>
                        <td class="text-success">{{ \App\Support\Money::format($inv->amount_paid) }}</td>
                        <td class="fw-bold text-danger">{{ \App\Support\Money::format($inv->balance) }}</td>
                        <td>
                            @if ($daysOverdue > 0)
                                <span class="badge bg-danger">{{ $daysOverdue }} days overdue</span>
                            @else
                                <span class="badge bg-warning text-dark">Due soon</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="bi bi-shield-check text-success fs-2 d-block mb-1"></i>
                            Excellent! There are no outstanding fee defaulters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
