@extends('Layouts.LayoutApp')
@section('title', 'Family Fee Statements')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Family Fee Invoices</h3>
        <small class="text-muted">Track tuition invoices, receipts, and balances for your enrolled children.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'receipt',
            'value' => \App\Support\Money::format($totalBilled),
            'label' => 'Total Invoiced',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check-circle-fill',
            'value' => \App\Support\Money::format($totalPaid),
            'label' => 'Total Paid',
            'tone' => 'success',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'exclamation-circle-fill',
            'value' => \App\Support\Money::format($totalDue),
            'label' => 'Total Balance Due',
            'tone' => $totalDue > 0 ? 'warning' : 'secondary',
        ])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Invoice No</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Term</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    <tr>
                        <td><code>{{ $inv->invoice_no }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->user->name }}</div>
                            <small class="text-muted">Adm: {{ $inv->student->admission_no }}</small>
                        </td>
                        <td>{{ $inv->student->class?->full_name ?? 'Unassigned' }}</td>
                        <td>{{ $inv->term ? $inv->term->name : 'General' }}</td>
                        <td>
                            <span class="{{ today()->gt($inv->due_date) && $inv->balance > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date->format('d M Y') }}
                            </span>
                        </td>
                        <td>{{ \App\Support\Money::format($inv->total) }}</td>
                        <td class="text-success fw-semibold">{{ \App\Support\Money::format($inv->amount_paid) }}</td>
                        <td>
                            <span class="fw-bold {{ $inv->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ \App\Support\Money::format($inv->balance) }}
                            </span>
                        </td>
                        <td>{!! $inv->status_badge !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-2 d-block mb-1"></i>
                            No fee invoices found for your children.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
