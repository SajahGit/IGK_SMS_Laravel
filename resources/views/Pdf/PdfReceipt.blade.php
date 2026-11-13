<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_no }}</title>
    <style>
        @page { margin: 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #244c86; padding-bottom: 14px; margin-bottom: 18px; }
        .header h1 { color: #244c86; font-size: 20px; margin-bottom: 5px; }
        .header p { color: #526179; margin-top: 3px; }
        h2 { font-size: 16px; margin-bottom: 5px; }
        .receipt-no { color: #244c86; font-size: 14px; margin-top: 7px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        td { border: 1px solid #d5dce7; padding: 8px; }
        td.label { width: 35%; color: #526179; }
        .amount { background: #edf7f0; border: 1px solid #a8d5b5; padding: 12px; margin: 16px 0; }
        .amount strong { font-size: 18px; }
        .words { margin-top: 6px; font-style: italic; }
        .reversed { color: #b42318; font-size: 18px; font-weight: bold; text-align: center; border: 2px solid #b42318; padding: 5px; margin-bottom: 12px; }
        .signatures { margin-top: 48px; text-align: center; }
        .signatures td { width: 50%; border: 0; border-top: 1px solid #7b8798; vertical-align: top; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    @if ($payment->isReversed()) <div class="reversed">REVERSED</div> @endif
    <header class="header">
        <h1>{{ $institution['name'] }}</h1>
        @if ($institution['address']) <p>{{ $institution['address'] }}</p> @endif
        @if ($institution['phone'] || $institution['email'])
            <p>{{ $institution['phone'] }}{{ $institution['phone'] && $institution['email'] ? ' | ' : '' }}{{ $institution['email'] }}</p>
        @endif
        <h2>Official Fee Payment Receipt</h2>
        <p class="receipt-no">{{ $payment->receipt_no }}</p>
    </header>

    <table>
        <tr><td class="label">Student Name</td><td><strong>{{ $payment->invoice->student->user->name }}</strong></td></tr>
        <tr><td class="label">Admission Number</td><td>{{ $payment->invoice->student->admission_no }}</td></tr>
        <tr><td class="label">Class &amp; Section</td><td>{{ $payment->invoice->student->class?->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Invoice Number</td><td>{{ $payment->invoice->invoice_no }}</td></tr>
        <tr><td class="label">Academic Term</td><td>{{ $payment->invoice->term?->name ?? 'General' }}</td></tr>
        <tr><td class="label">Payment Method</td><td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td></tr>
        <tr><td class="label">Payment Date</td><td>{{ $payment->paid_at->format('d F Y') }}</td></tr>
        <tr><td class="label">Cashier</td><td>{{ $payment->receiver?->name ?? 'System Admin' }}</td></tr>
        @if ($payment->reference)
            <tr><td class="label">Transaction Reference</td><td>{{ $payment->reference }}</td></tr>
        @endif
    </table>

    <div class="amount">
        <div>Amount received</div>
        <strong>{{ \App\Support\Money::format($payment->amount, $payment->currency.' ') }}</strong>
        <div class="words">{{ $amountInWords }}</div>
    </div>

    <table>
        <tr><td class="label">Invoice Total</td><td>{{ \App\Support\Money::format($payment->invoice->total, $payment->invoice->currency.' ') }}</td></tr>
        <tr><td class="label">Total Paid</td><td>{{ \App\Support\Money::format($payment->invoice->amount_paid, $payment->invoice->currency.' ') }}</td></tr>
        <tr><td class="label">Outstanding Balance</td><td>{{ \App\Support\Money::format($payment->invoice->balance, $payment->invoice->currency.' ') }}</td></tr>
    </table>

    @if ($payment->isReversed())
        <p class="reversed">Voided {{ $payment->reversed_at->format('d M Y, h:i A') }} — {{ $payment->reversal_reason }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>Cashier: {{ $payment->receiver?->name ?? 'System Admin' }}<br><span class="muted">Authorized Signature</span></td>
            <td>School Finance Department<br><span class="muted">Official Stamp</span></td>
        </tr>
    </table>
</body>
</html>
