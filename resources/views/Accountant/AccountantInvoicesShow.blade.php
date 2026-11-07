@extends('Layouts.LayoutApp')
@section('title', 'Invoice ' . $invoice->invoice_no)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="mb-0">Invoice <code>{{ $invoice->invoice_no }}</code></h3>
            {!! $invoice->status_badge !!}
        </div>
        <small class="text-muted">{{ $invoice->isDraft() ? 'Draft dated' : 'Issued on' }} {{ $invoice->issue_date->format('d M Y') }} &middot; Due by {{ $invoice->due_date->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($financeRoutePrefix.'.invoices.pdf', $invoice) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        @if ($invoice->balance > 0 && ! in_array($invoice->status, ['cancelled', 'draft'], true))
            <a href="{{ route($financeRoutePrefix.'.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success">
                <i class="bi bi-cash-stack me-1"></i> Record Payment
            </a>
        @endif
        @if ($invoice->isDraft() && \Illuminate\Support\Facades\Route::has($financeRoutePrefix.'.invoices.issue'))
            <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.issue', $invoice) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-check me-1"></i> Issue Invoice
                </button>
            </form>
        @endif
        @if (! $invoice->isCancelled() && \Illuminate\Support\Facades\Route::has($financeRoutePrefix.'.invoices.cancel'))
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelInvoiceModal">
                <i class="bi bi-x-octagon me-1"></i> Cancel Invoice
            </button>
        @endif
        <a href="{{ route($financeRoutePrefix.'.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoices
        </a>
    </div>
</div>

@include('Layouts.LayoutErrors')

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'receipt',
            'value' => \App\Support\Money::format($invoice->total),
            'label' => 'Total Amount Billed',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check-circle-fill',
            'value' => \App\Support\Money::format($invoice->amount_paid),
            'label' => 'Total Amount Paid',
            'tone' => 'success',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'exclamation-circle-fill',
            'value' => \App\Support\Money::format($invoice->balance),
            'label' => 'Outstanding Balance',
            'tone' => $invoice->balance > 0 ? 'warning' : 'secondary',
        ])
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent fw-semibold py-3">
                <i class="bi bi-person-fill text-primary me-1"></i> Student Information
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Full Name:</td>
                        <td class="fw-semibold">{{ $invoice->student->user->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Admission No:</td>
                        <td><code>{{ $invoice->student->admission_no }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Class & Section:</td>
                        <td>{{ $invoice->student->class?->full_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Guardian:</td>
                        <td>{{ $invoice->student->guardian?->user?->name ?: '—' }} ({{ $invoice->student->guardian?->phone ?: 'No phone' }})</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent fw-semibold py-3">
                <i class="bi bi-calendar-check text-info me-1"></i> Billing Term Details
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Term:</td>
                        <td class="fw-semibold">{{ $invoice->term ? $invoice->term->name : 'General (Annual)' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Issue Date:</td>
                        <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Due Date:</td>
                        <td>
                            <span class="{{ today()->gt($invoice->due_date) && $invoice->balance > 0 ? 'text-danger fw-bold' : '' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                                @if (today()->gt($invoice->due_date) && $invoice->balance > 0)
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Billed lines -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Billed Items</h5>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Description</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-end {{ \App\Support\Money::comp($item->amount, '0.00') < 0 ? 'text-success' : '' }}">{{ \App\Support\Money::format($item->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-3">No line items recorded.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr><th>Subtotal</th><th class="text-end">{{ \App\Support\Money::format($invoice->subtotal) }}</th></tr>
                @if (\App\Support\Money::comp($invoice->discount, '0.00') > 0)
                    <tr><th>Discount</th><th class="text-end text-success">- {{ \App\Support\Money::format($invoice->discount) }}</th></tr>
                @endif
                <tr><th>Total</th><th class="text-end">{{ \App\Support\Money::format($invoice->total) }}</th></tr>
            </tfoot>
        </table>
    </div>
    @if ($invoice->isCancelled())
        <div class="card-footer text-danger small">
            Cancelled {{ $invoice->cancelled_at?->format('d M Y, h:i A') }} — {{ $invoice->cancellation_reason }}
        </div>
    @endif
</div>

<!-- Payments History -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Payment History</h5>
        @if ($invoice->balance > 0 && ! in_array($invoice->status, ['cancelled', 'draft'], true))
            <a href="{{ route($financeRoutePrefix.'.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg me-1"></i> Record Payment
            </a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Receipt No</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Received By</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->payments as $payment)
                    <tr class="{{ $payment->isReversed() ? 'opacity-50 text-decoration-line-through' : '' }}">
                        <td><code>{{ $payment->receipt_no }}</code></td>
                        <td>{{ $payment->paid_at->format('d M Y') }}</td>
                        <td class="fw-bold">{{ \App\Support\Money::format($payment->amount) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                        <td>{{ $payment->reference ?: '—' }}</td>
                        <td>{{ $payment->receiver?->name ?: 'System' }}</td>
                        <td>
                            @if ($payment->isReversed())
                                <span class="badge bg-danger" title="Reversed: {{ $payment->reversal_reason }}">Reversed</span>
                            @else
                                <span class="badge bg-success">Completed</span>
                            @endif
                        </td>
                        <td class="text-end text-decoration-none">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route($financeRoutePrefix.'.receipt.show', $payment) }}" class="btn btn-outline-primary" title="Print Receipt">
                                    <i class="bi bi-printer me-1"></i> Receipt
                                </a>
                                @if (!$payment->isReversed() && auth()->user()->isSuperAdmin() && \Illuminate\Support\Facades\Route::has($financeRoutePrefix.'.payments.reverse'))
                                    <button type="button" class="btn btn-outline-danger reverse-btn"
                                            data-id="{{ $payment->id }}"
                                            data-receipt="{{ $payment->receipt_no }}"
                                            data-action="{{ route($financeRoutePrefix.'.payments.reverse', $payment) }}">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reverse
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-2 d-block mb-1"></i>
                            No payments recorded for this invoice yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Cancel Invoice Modal -->
@if (! $invoice->isCancelled() && \Illuminate\Support\Facades\Route::has($financeRoutePrefix.'.invoices.cancel'))
<div class="modal fade" id="cancelInvoiceModal" tabindex="-1" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.cancel', $invoice) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="cancelInvoiceModalLabel">Cancel Invoice {{ $invoice->invoice_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        A cancelled invoice is kept for the audit trail and can no longer receive payments.
                        An invoice that has payments must have them reversed first.
                    </div>
                    <label for="cancellation_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="cancellation_reason" id="cancellation_reason" class="form-control" required minlength="5" maxlength="255">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Reversal Modal -->
<div class="modal fade" id="reverseModal" tabindex="-1" aria-labelledby="reverseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="reverseForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="reverseModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reverse Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to reverse payment receipt <strong id="reverseReceiptNo"></strong>.</p>
                    <div class="alert alert-warning small">
                        Reversing this payment will restore the invoice balance and cannot be undone. An audit record will be generated.
                    </div>
                    <div class="mb-3">
                        <label for="reversal_reason" class="form-label">Reversal Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reversal_reason" id="reversal_reason" class="form-control" placeholder="e.g. Bounced cheque, data entry error" required minlength="5">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-arrow-counterclockwise me-1"></i> Confirm Reversal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var reverseModalEl = document.getElementById('reverseModal');
        if (!reverseModalEl) return;
        var modal = new bootstrap.Modal(reverseModalEl);
        var form = document.getElementById('reverseForm');
        var receiptSpan = document.getElementById('reverseReceiptNo');

        document.querySelectorAll('.reverse-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action = this.dataset.action;
                receiptSpan.innerText = this.dataset.receipt;
                modal.show();
            });
        });
    });
</script>
@endsection
