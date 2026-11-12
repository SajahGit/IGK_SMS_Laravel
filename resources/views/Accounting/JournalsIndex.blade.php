@extends('Layouts.LayoutApp')
@section('title', 'Journal Entries')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Journal Entries</h3><small class="text-muted">Manual and auto-posted general ledger entries.</small></div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.accounting.income-expense') }}" class="btn btn-outline-primary"><i class="bi bi-clipboard-data me-1"></i>Income &amp; expenses</a>
        <a href="{{ route('admin.accounting.transactions.create', ['type' => 'income']) }}" class="btn btn-outline-success"><i class="bi bi-arrow-down-left me-1"></i>Record income</a>
        <a href="{{ route('admin.accounting.transactions.create', ['type' => 'expense']) }}" class="btn btn-outline-danger"><i class="bi bi-arrow-up-right me-1"></i>Record expense</a>
        <form method="POST" action="{{ route('admin.journals.sync') }}" onsubmit="return confirm('Auto-post all unposted invoices and payments?')">
            @csrf
            <button class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>Auto-post Billing</button>
        </form>
        <a href="{{ route('admin.journals.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Entry</a>
    </div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
    <div class="col-md-3">
        <select name="branch_id" class="form-select">
            <option value="">All branches</option>
            @foreach ($branches as $b)<option value="{{ $b->id }}" @selected((string) request('branch_id') === (string) $b->id)>{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Entry No</th><th>Date</th><th>Description</th><th>Branch</th><th class="text-end">Amount</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($entries as $e)
                <tr>
                    <td><span class="badge bg-secondary">{{ $e->entry_no }}</span></td>
                    <td>{{ $e->entry_date->format('d M Y') }}</td>
                    <td>{{ $e->description }}
                        @if ($e->source_type === 'invoice' || $e->source_type === 'payment')
                            <span class="badge bg-info text-dark">Billing auto-post</span>
                        @elseif ($e->source_type === 'manual_income')
                            <span class="badge bg-success">Other income</span>
                        @elseif ($e->source_type === 'manual_expense')
                            <span class="badge bg-warning text-dark">Expense</span>
                        @endif
                    </td>
                    <td>{{ $e->branch?->name ?? '—' }}</td>
                    <td class="text-end">{{ number_format((float) $e->total_debit, 2) }}</td>
                    <td>@if ($e->status === 'posted')<span class="badge bg-success">Posted</span>@else<span class="badge bg-danger">Void</span>@endif</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.journals.show', $e) }}" class="btn btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
                            @if ($e->status === 'posted')
                            <form method="POST" action="{{ route('admin.journals.destroy', $e) }}" class="d-inline" onsubmit="return confirm('Void entry {{ $e->entry_no }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Void</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No journal entries.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $entries->links('pagination::bootstrap-5') }}</div>
@endsection
