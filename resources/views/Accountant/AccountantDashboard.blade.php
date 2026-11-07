@extends('Layouts.LayoutApp')
@section('title', 'Finance Dashboard')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-1">Finance Dashboard</h3>
        <p class="text-muted mb-0">Fee billing, collections, receipts, and outstanding balances.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accountant.invoices.create') }}" class="btn btn-primary">Generate Invoices</a>
        <a href="{{ route('accountant.payments.create') }}" class="btn btn-success">Record Payment</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'receipt', 'value' => $invoiceCount, 'label' => 'Active Invoices', 'tone' => 'primary'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'cash-stack', 'value' => \App\Support\Money::format($totalBilled), 'label' => 'Total Billed', 'tone' => 'info'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'check2-circle', 'value' => \App\Support\Money::format($totalCollected), 'label' => 'Total Collected', 'tone' => 'success'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'exclamation-circle', 'value' => \App\Support\Money::format($totalOutstanding), 'label' => 'Outstanding', 'tone' => 'warning'])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Payments</h5>
        <a href="{{ route('accountant.reports.fee-collection') }}" class="btn btn-sm btn-outline-primary">Fee Collection Report</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Receipt</th><th>Student</th><th>Invoice</th><th>Date</th><th class="text-end">Amount</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->receipt_no }}</td>
                        <td>{{ $payment->invoice->student->user->name }}</td>
                        <td>{{ $payment->invoice->invoice_no }}</td>
                        <td>{{ $payment->paid_at->format('d M Y') }}</td>
                        <td class="text-end">{{ \App\Support\Money::format($payment->amount) }}</td>
                        <td class="text-end"><a href="{{ route('accountant.receipt.show', $payment) }}" class="btn btn-sm btn-outline-secondary">Receipt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payments have been recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
