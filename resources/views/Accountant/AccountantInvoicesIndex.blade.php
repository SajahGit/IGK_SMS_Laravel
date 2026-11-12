@extends('Layouts.LayoutApp')
@section('title', 'Invoices & Billing')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Invoices & Billing</h3>
        <small class="text-muted">Manage term fees, sequential invoice tracking, and outstanding balances.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($financeRoutePrefix.'.invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-receipt-cutoff me-1"></i> Generate Class Invoices
        </a>
        <a href="{{ route($financeRoutePrefix.'.payments.create') }}" class="btn btn-success">
            <i class="bi bi-cash-stack me-1"></i> Record Payment
        </a>
    </div>
</div>

@include('Layouts.LayoutErrors')

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'receipt',
            'value' => \App\Support\Money::format($totalBilled),
            'label' => 'Total Billed',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check2-circle',
            'value' => \App\Support\Money::format($totalCollected),
            'label' => 'Total Collected',
            'tone' => 'success',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'exclamation-circle-fill',
            'value' => \App\Support\Money::format($totalOutstanding),
            'label' => 'Total Outstanding',
            'tone' => 'warning',
        ])
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($financeRoutePrefix.'.invoices.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control" placeholder="Search Invoice No, Student, Adm No..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="issued" @selected($status === 'issued')>Issued (Unpaid)</option>
                    <option value="partially_paid" @selected($status === 'partially_paid')>Partially Paid</option>
                    <option value="paid" @selected($status === 'paid')>Paid in Full</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Filter</button>
                @if ($search || $classId || $status)
                    <a href="{{ route($financeRoutePrefix.'.invoices.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.filters.store') }}" class="d-flex gap-2">
        @csrf
        <input type="hidden" name="filters[q]" value="{{ $search }}">
        <input type="hidden" name="filters[class_id]" value="{{ $classId }}">
        <input type="hidden" name="filters[status]" value="{{ $status }}">
        <input name="name" class="form-control form-control-sm" maxlength="80" placeholder="Name this filter" required>
        <button class="btn btn-sm btn-outline-primary">Save filter</button>
    </form>
    @foreach ($savedFilters as $savedFilter)
        <a class="btn btn-sm btn-outline-secondary" href="{{ route($financeRoutePrefix.'.invoices.index', $savedFilter->filters) }}">{{ $savedFilter->name }}</a>
        <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.filters.destroy', $savedFilter) }}">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $savedFilter->name }}">×</button>
        </form>
    @endforeach
</div>

@if ($classId && ($status === 'draft' || $invoices->contains(fn ($i) => $i->status === 'draft')) && \Illuminate\Support\Facades\Route::has($financeRoutePrefix.'.invoices.issue-drafts'))
    <form method="POST" action="{{ route($financeRoutePrefix.'.invoices.issue-drafts') }}" class="mb-3" onsubmit="return confirm('Issue every draft invoice of this class?')">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-check me-1"></i> Issue all drafts of this class</button>
    </form>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Invoice No</th>
                    <th>Student Details</th>
                    <th>Term</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route($financeRoutePrefix.'.invoices.show', $inv) }}" class="fw-bold text-decoration-none">
                                <code>{{ $inv->invoice_no }}</code>
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->user->name }}</div>
                            <small class="text-muted">{{ $inv->student->class?->full_name ?? 'Unassigned' }} &middot; Adm: {{ $inv->student->admission_no }}</small>
                        </td>
                        <td>{{ $inv->term ? $inv->term->name : 'General' }}</td>
                        <td>
                            <span class="{{ today()->gt($inv->due_date) && $inv->balance > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date->format('d M Y') }}
                            </span>
                        </td>
                        <td>{{ \App\Support\Money::format($inv->total) }}</td>
                        <td class="text-success">{{ \App\Support\Money::format($inv->amount_paid) }}</td>
                        <td>
                            <span class="fw-bold {{ $inv->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ \App\Support\Money::format($inv->balance) }}
                            </span>
                        </td>
                        <td>{!! $inv->status_badge !!}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route($financeRoutePrefix.'.invoices.show', $inv) }}" class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($inv->balance > 0 && ! in_array($inv->status, ['cancelled', 'draft'], true))
                                    <a href="{{ route($financeRoutePrefix.'.payments.create', ['invoice_id' => $inv->id]) }}" class="btn btn-outline-success" title="Record Payment">
                                        <i class="bi bi-credit-card me-1"></i> Pay
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-1"></i>
                            No invoices found matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $invoices->links('pagination::bootstrap-5') }}</div>
@endsection
