@extends('Layouts.LayoutApp')
@section('title', 'Generate Class Invoices')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Generate Class Invoices</h3>
        <small class="text-muted">Bulk-generate sequential invoices for an entire section with automatic deduplication.</small>
    </div>
    <a href="{{ route($financeRoutePrefix.'.invoices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Invoices
    </a>
</div>

@include('Layouts.LayoutErrors')

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.store') }}" id="bulkInvoiceForm">
                    @csrf

                    <div class="mb-3">
                        <label for="class_select" class="form-label">Target Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_select" class="form-select" onchange="window.location.href = '{{ route($financeRoutePrefix.'.invoices.create') }}?class_id=' + this.value" required>
                            <option value="">Select Class to Invoice</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>
                                    {{ $c->full_name }} ({{ $c->students_count }} enrolled students)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="term_id" class="form-label">Academic Term (Optional)</label>
                        <select name="term_id" id="term_id" class="form-select">
                            <option value="">General (No specific term)</option>
                            @foreach ($terms as $t)
                                <option value="{{ $t->id }}" @selected(old('term_id') == $t->id || $t->is_current)>
                                    {{ $t->academicYear->name }} - {{ $t->name }} {{ $t->is_current ? '(Active Term)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="issue_date" class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ old('issue_date', today()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', today()->addDays(30)->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="discount" class="form-label">Discount per student (optional)</label>
                        <input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control" value="{{ old('discount') }}" placeholder="0.00">
                        <div class="form-text">Applied as a separate negative line on every invoice generated in this run. It cannot exceed the amount billed.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Fee Items to Include <span class="text-danger">*</span></label>
                        @if ($feeStructures->isNotEmpty())
                            <div class="list-group">
                                @foreach ($feeStructures as $fee)
                                    <div class="list-group-item">
                                        <label class="d-flex justify-content-between align-items-center mb-0">
                                            <div>
                                                <input class="form-check-input me-2 fee-checkbox" type="checkbox" name="fee_structure_ids[]" value="{{ $fee->id }}" data-amount="{{ $fee->amount }}" data-currency="{{ $fee->currency }}" checked>
                                                <span class="fw-semibold">{{ $fee->name }}</span>
                                                @if ($fee->term)
                                                    <small class="text-muted">({{ $fee->term->name }})</small>
                                                @endif
                                            </div>
                                            <span class="fw-bold text-primary">{{ \App\Support\Money::format($fee->amount, $fee->currency.' ') }}</span>
                                        </label>
                                        @foreach ($fee->items as $item)
                                            <div class="ms-4 mt-1 small d-flex justify-content-between">
                                                <span>
                                                    @if ($item->is_mandatory)
                                                        <span class="badge bg-primary me-1">Mandatory</span>
                                                    @else
                                                        <input class="form-check-input me-1 optional-item-checkbox" type="checkbox" name="fee_item_ids[]" value="{{ $item->id }}" data-amount="{{ $item->amount }}" data-currency="{{ $fee->currency }}" id="opt-{{ $item->id }}">
                                                        <span class="badge bg-secondary me-1">Optional</span>
                                                    @endif
                                                    <label for="opt-{{ $item->id }}" class="mb-0">{{ $item->name }}</label>
                                                </span>
                                                <span class="text-muted">{{ \App\Support\Money::format($item->amount, $fee->currency.' ') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning py-3">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                No active fee structures configured for this class.
                                <a href="{{ route($financeRoutePrefix.'.fee-structures.index', ['class_id' => $selectedClassId]) }}" class="alert-link">Create fee structure</a> first.
                            </div>
                        @endif
                    </div>

                    @if ($feeStructures->isNotEmpty())
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="as_draft" id="as_draft" value="1" @checked(old('as_draft'))>
                            <label class="form-check-label" for="as_draft">Save as draft (students cannot see it and no payment can be recorded until you issue it)</label>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <div>
                                <span class="text-muted">Total Invoice Amount Per Student:</span>
                                <div class="fs-4 fw-bold text-primary" id="totalPreview">LKR 0.00</div>
                            </div>
                            <button type="submit" id="issueInvoices" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-receipt-cutoff me-1"></i> Issue Invoices
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 bg-body-tertiary">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Idempotent Invoicing</h5>
                <p class="small text-muted mb-3">
                    The invoicing engine automatically detects existing invoices for each student and skips duplicate generation for the specified term.
                </p>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Sequential invoice numbering (`INV-YYYY-XXXX`) generated under exclusive row locking.</li>
                    <li>Students already invoiced for this term will not be billed twice.</li>
                    <li>Audit trail entry recorded for every invoice created.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateTotal() {
            var total = 0.0;
            var currencies = new Set();
            document.querySelectorAll('.fee-checkbox:checked').forEach(function (c) {
                total += parseFloat(c.dataset.amount || 0);
                currencies.add(c.dataset.currency || 'LKR');
            });
            document.querySelectorAll('.optional-item-checkbox:checked').forEach(function (c) {
                total += parseFloat(c.dataset.amount || 0);
                currencies.add(c.dataset.currency || 'LKR');
            });
            var el = document.getElementById('totalPreview');
            if (el) {
                var button = document.getElementById('issueInvoices');
                if (currencies.size > 1) {
                    el.innerText = 'Choose fees in one currency';
                    el.classList.add('text-danger');
                    if (button) button.disabled = true;
                    return;
                }
                el.classList.remove('text-danger');
                var currency = currencies.size ? Array.from(currencies)[0] : 'LKR';
                el.innerText = currency + ' ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                if (button) button.disabled = false;
            }
        }
        document.querySelectorAll('.fee-checkbox, .optional-item-checkbox').forEach(function (c) {
            c.addEventListener('change', updateTotal);
        });
        updateTotal();
    });
</script>
@endsection
