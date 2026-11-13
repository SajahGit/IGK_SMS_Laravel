@extends('Layouts.LayoutApp')
@section('title', 'Receipt ' . $payment->receipt_no)

@section('content')
<div class="no-print d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">Payment Receipt</h3>
        <small class="text-muted">Official electronic voucher for fee payment transaction.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($financeRoutePrefix.'.receipt.pdf', $payment) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print Receipt
        </button>
        <a href="{{ route($financeRoutePrefix.'.invoices.show', $payment->invoice_id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoice
        </a>
    </div>
</div>

<div class="receipt-container">
    <div class="card shadow-sm border-0 receipt-sheet p-4 p-md-5 mx-auto" style="max-width: 800px;">
        <!-- Header -->
        <div class="text-center pb-4 mb-4 border-bottom position-relative">
            @if ($payment->isReversed())
                <div class="position-absolute top-50 start-50 translate-middle opacity-25">
                    <h1 class="display-1 text-danger fw-bold text-uppercase" style="transform: rotate(-15deg); letter-spacing: 10px;">REVERSED</h1>
                </div>
            @endif
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <span class="brand-mark fs-3 text-primary"><i class="bi bi-mortarboard-fill"></i></span>
                <h3 class="h3 mb-0 fw-bold">IGK SCHOOL</h3>
            </div>
            <div class="text-muted small text-uppercase tracking-widest fw-semibold mb-2">Official Fee Payment Receipt</div>
            <div class="fs-5 fw-bold text-primary"><code>{{ $payment->receipt_no }}</code></div>
        </div>

        <!-- Meta Details -->
        <div class="row g-3 mb-4 p-3 rounded bg-body-tertiary">
            <div class="col-sm-6">
                <div class="text-muted small text-uppercase">Payment Date</div>
                <div class="fw-bold">{{ $payment->paid_at->format('d F Y') }}</div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="text-muted small text-uppercase">Invoice Reference</div>
                <div class="fw-bold"><code>{{ $payment->invoice->invoice_no }}</code></div>
            </div>
        </div>

        <div class="card border mb-4">
            <div class="card-body p-3">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 30%;">Student Name:</td>
                        <td class="fw-bold fs-6">{{ $payment->invoice->student->user->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Admission Number:</td>
                        <td><code>{{ $payment->invoice->student->admission_no }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Class & Section:</td>
                        <td>{{ $payment->invoice->student->class?->full_name ?? 'Unassigned' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Academic Term:</td>
                        <td>{{ $payment->invoice->term ? $payment->invoice->term->name : 'General' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payment Mode:</td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                    </tr>
                    @if ($payment->reference)
                        <tr>
                            <td class="text-muted">Transaction / Slip Ref:</td>
                            <td><code>{{ $payment->reference }}</code></td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Amount Box -->
        <div class="p-3 mb-4 rounded border bg-success-subtle text-success-emphasis text-center">
            <div class="small text-uppercase fw-semibold mb-1">Amount Received</div>
            <div class="display-6 fw-bold text-success">{{ \App\Support\Money::format($payment->amount, $payment->currency.' ') }}</div>
        </div>

        <!-- Remaining Balance Summary -->
        <div class="row g-2 mb-5 small text-muted">
            <div class="col-4 text-center p-2 rounded bg-body-tertiary">
                <div>Total Billed</div>
                <div class="fw-bold text-body">{{ \App\Support\Money::format($payment->invoice->total, $payment->invoice->currency.' ') }}</div>
            </div>
            <div class="col-4 text-center p-2 rounded bg-body-tertiary">
                <div>Total Paid to Date</div>
                <div class="fw-bold text-success">{{ \App\Support\Money::format($payment->invoice->amount_paid, $payment->invoice->currency.' ') }}</div>
            </div>
            <div class="col-4 text-center p-2 rounded bg-body-tertiary">
                <div>Remaining Balance</div>
                <div class="fw-bold {{ $payment->invoice->balance > 0 ? 'text-danger' : 'text-muted' }}">
                    {{ \App\Support\Money::format($payment->invoice->balance, $payment->invoice->currency.' ') }}
                </div>
            </div>
        </div>

        @if ($payment->isReversed())
            <div class="alert alert-danger mb-4">
                <strong><i class="bi bi-exclamation-octagon-fill me-1"></i> Transaction Reversed:</strong>
                This receipt was voided on {{ $payment->reversed_at->format('d M Y, h:i A') }}.
                Reason: {{ $payment->reversal_reason }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="row pt-5 text-center">
            <div class="col-6">
                <div class="border-bottom pb-4 mb-2 mx-auto" style="width: 70%;"></div>
                <div class="fw-semibold small">Cashier: {{ $payment->receiver?->name ?: 'System Admin' }}</div>
                <small class="text-muted">Authorized Signature</small>
            </div>
            <div class="col-6">
                <div class="border-bottom pb-4 mb-2 mx-auto" style="width: 70%;"></div>
                <div class="fw-semibold small">School Finance Department</div>
                <small class="text-muted">Official Stamp</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .topbar, .app-footer, .bg-orb-3, .sidebar-backdrop {
        display: none !important;
    }
    .app-shell { display: block !important; }
    .app-main { margin-left: 0 !important; padding: 0 !important; }
    .content { padding: 0 !important; }
    body { background: #ffffff !important; color: #000000 !important; }
    .receipt-sheet { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
}
</style>
@endsection
