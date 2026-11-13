<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        @page { margin: 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #244c86; padding-bottom: 14px; margin-bottom: 18px; }
        .header h1 { color: #244c86; font-size: 20px; margin-bottom: 5px; }
        .header p { color: #526179; margin-top: 3px; }
        h2 { font-size: 16px; margin: 12px 0 5px; }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
        td, th { border: 1px solid #d5dce7; padding: 8px; text-align: left; }
        th { background: #edf2f8; }
        .label { color: #526179; width: 34%; }
        .totals { width: 55%; margin-left: auto; }
        .right { text-align: right; }
        .status { text-transform: uppercase; font-weight: bold; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $institution['name'] }}</h1>
        @if ($institution['address']) <p>{{ $institution['address'] }}</p> @endif
        @if ($institution['phone'] || $institution['email'])
            <p>{{ $institution['phone'] }}{{ $institution['phone'] && $institution['email'] ? ' | ' : '' }}{{ $institution['email'] }}</p>
        @endif
        <h2>Student Fee Invoice</h2>
        <p>{{ $invoice->invoice_no }}</p>
    </header>

    <table>
        <tr><td class="label">Student Name</td><td>{{ $invoice->student->user->name }}</td></tr>
        <tr><td class="label">Admission Number</td><td>{{ $invoice->student->admission_no }}</td></tr>
        <tr><td class="label">Class &amp; Section</td><td>{{ $invoice->student->class?->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Guardian</td><td>{{ $invoice->student->guardian?->user?->name ?? '—' }}</td></tr>
        <tr><td class="label">Academic Term</td><td>{{ $invoice->term?->name ?? 'General / Annual' }}</td></tr>
        <tr><td class="label">Issue Date</td><td>{{ $invoice->issue_date->format('d F Y') }}</td></tr>
        <tr><td class="label">Due Date</td><td>{{ $invoice->due_date->format('d F Y') }}</td></tr>
        <tr><td class="label">Status</td><td class="status">{{ str_replace('_', ' ', $invoice->status) }}</td></tr>
    </table>

    <table class="totals">
        <tr><th>Description</th><th class="right">Amount</th></tr>
        @forelse ($invoice->items as $item)
            <tr><td>{{ $item->description }}</td><td class="right">{{ \App\Support\Money::format($item->amount, $invoice->currency.' ') }}</td></tr>
        @empty
            <tr><td>Fees billed for this term</td><td class="right">{{ \App\Support\Money::format($invoice->total, $invoice->currency.' ') }}</td></tr>
        @endforelse
        <tr><td><strong>Total</strong></td><td class="right"><strong>{{ \App\Support\Money::format($invoice->total, $invoice->currency.' ') }}</strong></td></tr>
        <tr><td>Amount paid</td><td class="right">{{ \App\Support\Money::format($invoice->amount_paid, $invoice->currency.' ') }}</td></tr>
        <tr><td><strong>Outstanding balance</strong></td><td class="right"><strong>{{ \App\Support\Money::format($invoice->balance, $invoice->currency.' ') }}</strong></td></tr>
    </table>

    <h2>Payment History</h2>
    <table>
        <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->receipt_no }}</td>
                    <td>{{ $payment->paid_at->format('d M Y') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                    <td>{{ \App\Support\Money::format($payment->amount, $payment->currency.' ') }}</td>
                    <td>{{ $payment->isReversed() ? 'Reversed' : 'Received' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No payments have been recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
