@extends('Layouts.LayoutApp')
@section('title', 'Record Payment')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Record Fee Payment</h3>
        <small class="text-muted">Enter verified student fee payments and issue official receipts.</small>
    </div>
    <a href="{{ route($financeRoutePrefix.'.invoices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Invoices
    </a>
</div>

@include('Layouts.LayoutErrors')

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Payment Details</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route($financeRoutePrefix.'.payments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="invoice_id" class="form-label">Invoice <span class="text-danger">*</span></label>
                        <select name="invoice_id" id="invoice_id" class="form-select" required>
                            @if ($selectedInvoice)
                                <option value="{{ $selectedInvoice->id }}" data-balance="{{ $selectedInvoice->balance }}" selected>
                                    {{ $selectedInvoice->invoice_no }} &middot; {{ $selectedInvoice->student->user->name }} ({{ $selectedInvoice->student->class->full_name }}) &middot; Due: {{ \App\Support\Money::format($selectedInvoice->balance) }}
                                </option>
                            @else
                                <option value="">Select Invoice with Pending Balance</option>
                                @foreach ($pendingInvoices as $inv)
                                    <option value="{{ $inv->id }}" data-balance="{{ $inv->balance }}" @selected(old('invoice_id') == $inv->id)>
                                        {{ $inv->invoice_no }} &middot; {{ $inv->student->user->name }} ({{ $inv->student->class->full_name }}) &middot; Due: {{ \App\Support\Money::format($inv->balance) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                   class="form-control"
                                   value="{{ old('amount', $selectedInvoice ? $selectedInvoice->balance : '') }}"
                                   placeholder="0.00" required>
                            <div class="form-text small text-muted">Overpayments beyond outstanding invoice balance are strictly rejected.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="paid_at" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="paid_at" id="paid_at" class="form-control"
                                   value="{{ old('paid_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="method" id="method" class="form-select" required>
                                <option value="cash" @selected(old('method') === 'cash')>Cash</option>
                                <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Reference / Bank Slip No</label>
                            <input type="text" name="reference" id="reference" class="form-control"
                                   value="{{ old('reference') }}" placeholder="e.g. Slip #98234, Transfer Ref">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route($financeRoutePrefix.'.invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check2-circle me-1"></i> Record & Issue Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var invoiceSelect = document.getElementById('invoice_id');
        var amountInput = document.getElementById('amount');
        if (invoiceSelect && amountInput) {
            invoiceSelect.addEventListener('change', function () {
                var selected = this.options[this.selectedIndex];
                if (selected && selected.dataset.balance) {
                    amountInput.value = selected.dataset.balance;
                }
            });
        }
    });
</script>
@endsection
