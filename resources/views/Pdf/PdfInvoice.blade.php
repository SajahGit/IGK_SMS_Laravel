<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: a4 portrait; margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { color: #1e3a8a; font-size: 19px; margin-bottom: 4px; }
        .header p { color: #475569; font-size: 9.5px; margin-top: 2px; }
        .invoice-title { font-size: 14px; font-weight: bold; margin-top: 8px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .invoice-no { color: #2563eb; font-size: 13px; font-weight: bold; margin-top: 2px; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 12px; }
        td, th { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: middle; }
        th { background: #f1f5f9; color: #1e293b; font-weight: bold; }
        .meta-table td { width: 50%; vertical-align: top; padding: 5px 8px; }
        .label { color: #64748b; font-size: 8.5px; text-transform: uppercase; }
        .val { font-weight: 600; color: #0f172a; }
        .right { text-align: right; }
        .center { text-align: center; }
        .status-badge { text-transform: uppercase; font-weight: bold; font-size: 9px; }
        .status-paid { color: #15803d; }
        .status-partial { color: #b45309; }
        .status-issued { color: #1d4ed8; }
        .status-cancelled { color: #b91c1c; }
        .status-draft { color: #4b5563; }
        .totals-row td { background: #f8fafc; font-weight: 600; }
        .balance-row td { background: #eff6ff; font-weight: bold; color: #1e3a8a; font-size: 11px; }
        .muted { color: #64748b; text-align: center; }
        .signatures { margin-top: 35px; text-align: center; page-break-inside: avoid; }
        .signatures td { width: 50%; padding: 0 20px; border: none; }
        .line { border-top: 1px solid #94a3b8; padding-top: 6px; font-weight: bold; font-size: 9.5px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $institution['name'] }}</h1>
        @if ($institution['address']) <p>{{ $institution['address'] }}</p> @endif
        @if ($institution['phone'] || $institution['email'])
            <p>{{ $institution['phone'] }}{{ $institution['phone'] && $institution['email'] ? ' | ' : '' }}{{ $institution['email'] }}</p>
        @endif
        <div class="invoice-title">Student Fee Invoice</div>
        <div class="invoice-no">{{ $invoice->invoice_no }}</div>
    </header>

    <table class="meta-table">
        <tr>
            <td><span class="label">Student Name:</span> <span class="val">{{ $invoice->student->user->name }}</span></td>
            <td><span class="label">Invoice Date:</span> <span class="val">{{ $invoice->issue_date->format('d F Y') }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Admission No:</span> <span class="val">{{ $invoice->student->admission_no }}</span></td>
            <td><span class="label">Due Date:</span> <span class="val">{{ $invoice->due_date->format('d F Y') }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Class &amp; Section:</span> <span class="val">{{ $invoice->student->class?->full_name ?? '—' }}</span></td>
            <td><span class="label">Academic Term:</span> <span class="val">{{ $invoice->term?->name ?? 'General / Annual' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Guardian:</span> <span class="val">{{ $invoice->student->guardian?->user?->name ?? '—' }}</span></td>
            <td>
                <span class="label">Status:</span>
                <span class="status-badge status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span>
            </td>
        </tr>
    </table>

    <h2>Fee Breakdown &amp; Charges</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;" class="center">#</th>
                <th style="width: 62%;">Description</th>
                <th style="width: 30%;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $lineNum = 0; @endphp
            @forelse ($invoice->items as $item)
                @php $lineNum++; @endphp
                <tr>
                    <td class="center">{{ $lineNum }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ \App\Support\Money::format($item->amount, $invoice->currency.' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center">1</td>
                    <td>Fees billed for this term</td>
                    <td class="right">{{ \App\Support\Money::format($invoice->total, $invoice->currency.' ') }}</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="right">Total Billed:</td>
                <td class="right">{{ \App\Support\Money::format($invoice->total, $invoice->currency.' ') }}</td>
            </tr>
            <tr class="totals-row">
                <td colspan="2" class="right">Amount Paid:</td>
                <td class="right" style="color: #15803d;">{{ \App\Support\Money::format($invoice->amount_paid, $invoice->currency.' ') }}</td>
            </tr>
            <tr class="balance-row">
                <td colspan="2" class="right">Outstanding Balance:</td>
                <td class="right">{{ \App\Support\Money::format($invoice->balance, $invoice->currency.' ') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Payment Transactions</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Receipt No</th>
                <th style="width: 20%;">Date</th>
                <th style="width: 20%;">Method</th>
                <th style="width: 20%;" class="right">Amount</th>
                <th style="width: 15%;" class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->receipt_no }}</td>
                    <td>{{ $payment->paid_at->format('d M Y') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                    <td class="right">{{ \App\Support\Money::format($payment->amount, $payment->currency.' ') }}</td>
                    <td class="center" style="font-weight: 600; color: {{ $payment->isReversed() ? '#b91c1c' : '#15803d' }};">
                        {{ $payment->isReversed() ? 'Reversed' : 'Received' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No payments have been recorded for this invoice yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="line">Accountant / Cashier Signature</div></td>
            <td><div class="line">School Finance Seal &amp; Stamp</div></td>
        </tr>
    </table>
</body>
</html>
