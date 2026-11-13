@extends('Layouts.LayoutApp')
@section('title', 'My Fee Invoices')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">My Fee Invoices</h3>
        <small class="text-muted">Review tuition and term fee statements, payment history, and balances.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'receipt',
            'value' => \App\Support\Money::formatTotals($totalBilled),
            'label' => 'Total Invoiced',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check-circle-fill',
            'value' => \App\Support\Money::formatTotals($totalPaid),
            'label' => 'Total Paid',
            'tone' => 'success',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'exclamation-circle-fill',
            'value' => \App\Support\Money::formatTotals($totalDue),
            'label' => 'Outstanding Due',
            'tone' => \App\Support\Money::hasPositiveTotal($totalDue) ? 'warning' : 'secondary',
        ])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Invoice No</th>
                    <th>Term / Cycle</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Total Billed</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    <tr>
                        <td><code>{{ $inv->invoice_no }}</code></td>
                        <td>{{ $inv->term ? $inv->term->name : 'General' }}</td>
                        <td>{{ $inv->issue_date->format('d M Y') }}</td>
                        <td>
                            <span class="{{ today()->gt($inv->due_date) && $inv->balance > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date->format('d M Y') }}
                            </span>
                        </td>
                        <td>{{ \App\Support\Money::format($inv->total, $inv->currency.' ') }}</td>
                        <td class="text-success fw-semibold">{{ \App\Support\Money::format($inv->amount_paid, $inv->currency.' ') }}</td>
                        <td>
                            <span class="fw-bold {{ $inv->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ \App\Support\Money::format($inv->balance, $inv->currency.' ') }}
                            </span>
                        </td>
                        <td>{!! $inv->status_badge !!}</td>
                        <td>
                            <a href="{{ route('student.invoices.pdf', $inv) }}" class="btn btn-sm btn-outline-primary">
                                Download PDF
                            </a>
                            @if ($inv->balance > 0 && in_array($inv->status, ['issued', 'partially_paid'], true) && config('services.payhere.merchant_id') && $inv->currency === config('services.payhere.currency', 'LKR'))
                                <a href="{{ route('student.payhere.create', $inv) }}" class="btn btn-sm btn-primary">Pay online</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-2 d-block mb-1"></i>
                            No invoices issued for your account.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
