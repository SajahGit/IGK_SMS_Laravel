<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_no }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: a4 portrait; margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { color: #1e3a8a; font-size: 19px; margin-bottom: 4px; }
        .header p { color: #475569; font-size: 9.5px; margin-top: 2px; }
        .receipt-title { font-size: 14px; font-weight: bold; margin-top: 8px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .receipt-no { color: #2563eb; font-size: 13px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 10px 0; }
        td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
        td.label { width: 32%; color: #64748b; font-size: 8.5px; text-transform: uppercase; }
        td.val { width: 68%; font-weight: 600; color: #0f172a; }
        .amount-box { background: #f0fdf4; border: 1px solid #86efac; padding: 12px; margin: 14px 0; text-align: center; border-radius: 3px; }
        .amount-title { color: #166534; font-size: 9px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
        .amount-val { font-size: 20px; font-weight: bold; color: #15803d; margin: 4px 0; }
        .amount-words { font-style: italic; color: #334155; font-size: 9.5px; }
        .reversed { color: #b91c1c; font-size: 16px; font-weight: bold; text-align: center; border: 2px solid #b91c1c; padding: 6px; margin-bottom: 12px; }
        .summary-table td { width: 33.33%; text-align: center; padding: 7px; background: #f8fafc; }
        .signatures { margin-top: 40px; text-align: center; page-break-inside: avoid; }
        .signatures td { width: 50%; padding: 0 25px; border: none; vertical-align: top; }
        .line { border-top: 1px solid #94a3b8; padding-top: 6px; font-weight: bold; font-size: 9.5px; }
        .muted { color: #64748b; font-size: 8.5px; }
    </style>
</head>
<body>
    @if ($payment->isReversed())
        <div class="reversed">VOID / TRANSACTION REVERSED</div>
    @endif

    <header class="header">
        <h1>{{ $institution['name'] }}</h1>
        @if ($institution['address']) <p>{{ $institution['address'] }}</p> @endif
        @if ($institution['phone'] || $institution['email'])
            <p>{{ $institution['phone'] }}{{ $institution['phone'] && $institution['email'] ? ' | ' : '' }}{{ $institution['email'] }}</p>
        @endif
        <div class="receipt-title">Official Fee Payment Receipt</div>
        <div class="receipt-no">{{ $payment->receipt_no }}</div>
    </header>

    <table>
        <tr><td class="label">Student Name</td><td class="val">{{ $payment->invoice->student->user->name }}</td></tr>
        <tr><td class="label">Admission Number</td><td class="val">{{ $payment->invoice->student->admission_no }}</td></tr>
        <tr><td class="label">Class &amp; Section</td><td class="val">{{ $payment->invoice->student->class?->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Invoice Reference</td><td class="val">{{ $payment->invoice->invoice_no }}</td></tr>
        <tr><td class="label">Academic Term</td><td class="val">{{ $payment->invoice->term?->name ?? 'General' }}</td></tr>
        <tr><td class="label">Payment Method</td><td class="val">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td></tr>
        <tr><td class="label">Payment Date</td><td class="val">{{ $payment->paid_at->format('d F Y') }}</td></tr>
        <tr><td class="label">Cashier / Receiver</td><td class="val">{{ $payment->receiver?->name ?? 'System Admin' }}</td></tr>
        @if ($payment->reference)
            <tr><td class="label">Transaction Reference</td><td class="val">{{ $payment->reference }}</td></tr>
        @endif
    </table>

    <div class="amount-box">
        <div class="amount-title">Amount Received</div>
        <div class="amount-val">{{ \App\Support\Money::format($payment->amount, $payment->currency.' ') }}</div>
        <div class="amount-words">{{ $amountInWords }}</div>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <span class="muted" style="display: block; text-transform: uppercase;">Invoice Total</span>
                <strong>{{ \App\Support\Money::format($payment->invoice->total, $payment->invoice->currency.' ') }}</strong>
            </td>
            <td>
                <span class="muted" style="display: block; text-transform: uppercase;">Total Paid to Date</span>
                <strong style="color: #15803d;">{{ \App\Support\Money::format($payment->invoice->amount_paid, $payment->invoice->currency.' ') }}</strong>
            </td>
            <td>
                <span class="muted" style="display: block; text-transform: uppercase;">Outstanding Balance</span>
                <strong style="color: {{ $payment->invoice->balance > 0 ? '#b91c1c' : '#1e3a8a' }};">{{ \App\Support\Money::format($payment->invoice->balance, $payment->invoice->currency.' ') }}</strong>
            </td>
        </tr>
    </table>

    @if ($payment->isReversed())
        <p class="reversed" style="font-size: 10px; margin-top: 10px;">Voided {{ $payment->reversed_at->format('d M Y, h:i A') }} — {{ $payment->reversal_reason }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="line">Cashier: {{ $payment->receiver?->name ?? 'System Admin' }}</div>
                <div class="muted">Authorized Signature</div>
            </td>
            <td>
                <div class="line">School Finance Department</div>
                <div class="muted">Official Seal &amp; Stamp</div>
            </td>
        </tr>
    </table>
</body>
</html>
