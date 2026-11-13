@extends('Layouts.LayoutApp')
@section('title', 'Fee Collection Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Fee Collection & Revenue Report</h3>
        <small class="text-muted">R9 · Receipt-level collections filtered by date, term, program and method.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route($reportRoutePrefix.'.pdf', ['report' => 'fee-collection'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route($reportRoutePrefix.'.fee-collection.csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'cash-coin',
            'value' => \App\Support\Money::formatTotals($totalCollected),
            'label' => 'Total Collected in Period',
            'tone' => 'success',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'wallet2',
            'value' => \App\Support\Money::formatTotals($byMethod['cash']['sum'] ?? []),
            'label' => 'Cash Collections (' . ($byMethod['cash']['count'] ?? 0) . ' tx)',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'bank',
            'value' => \App\Support\Money::formatTotals($byMethod['bank_transfer']['sum'] ?? []),
            'label' => 'Bank Transfers (' . ($byMethod['bank_transfer']['count'] ?? 0) . ' tx)',
            'tone' => 'info',
        ])
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($reportRoutePrefix.'.fee-collection') }}" class="row g-2 align-items-center">
            <div class="col-md-2">
                <label for="from" class="form-label small fw-semibold">From Date</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <label for="term_id" class="form-label small fw-semibold">Term</label>
                <select name="term_id" id="term_id" class="form-select">
                    <option value="">All terms</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="program_id" class="form-label small fw-semibold">Program</label>
                <select name="program_id" id="program_id" class="form-select">
                    <option value="">All programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected($programId == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="method" class="form-label small fw-semibold">Payment Method</label>
                <select name="method" id="method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="cash" @selected($method === 'cash')>Cash</option>
                    <option value="bank_transfer" @selected($method === 'bank_transfer')>Bank Transfer</option>
                </select>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i> Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Receipt No</th>
                    <th>Date</th>
                    <th>Term</th>
                    <th>Program</th>
                    <th>Invoice No</th>
                    <th>Student Details</th>
                    <th>Class</th>
                    <th>Amount (invoice currency)</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Cashier</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $p)
                    <tr class="{{ $p->isReversed() ? 'opacity-50 text-decoration-line-through' : '' }}">
                        <td>
                            <a href="{{ route($financeRoutePrefix.'.receipt.show', $p) }}" class="fw-bold">
                                <code>{{ $p->receipt_no }}</code>
                            </a>
                        </td>
                        <td>{{ $p->paid_at->format('d M Y') }}</td>
                        <td>{{ $p->invoice->term?->name ?? '—' }}</td>
                        <td>{{ $p->invoice->student->program?->name ?? $p->invoice->student->class?->program?->name ?? '—' }}</td>
                        <td><code>{{ $p->invoice->invoice_no }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $p->invoice->student->user->name }}</div>
                            <small class="text-muted">Adm: {{ $p->invoice->student->admission_no }}</small>
                        </td>
                        <td>{{ $p->invoice->student->class?->full_name ?? '—' }}</td>
                        <td class="fw-bold text-success">{{ \App\Support\Money::format($p->amount, $p->currency.' ') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $p->method)) }}</td>
                        <td>{{ $p->reference ?: '—' }}</td>
                        <td>{{ $p->receiver?->name ?: 'System' }}</td>
                        <td>
                            @if ($p->isReversed())
                                <span class="badge bg-danger">Reversed</span>
                            @else
                                <span class="badge bg-success">Received</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-4 text-muted">
                            <i class="bi bi-cash-stack fs-2 d-block mb-1"></i>
                            No fee payments recorded for this criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
