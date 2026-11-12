@extends('Layouts.LayoutApp')
@section('title', 'Income and Expenses')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-1">Income and Expenses</h3><p class="text-muted mb-0">Posted ledger entries only. Fee invoices and receipts are included through automatic billing journals.</p></div>
    <a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary">Journal entries</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><label class="form-label" for="from">From</label><input id="from" name="from" type="date" class="form-control" value="{{ $from }}" required></div>
    <div class="col-md-3"><label class="form-label" for="to">To</label><input id="to" name="to" type="date" class="form-control" value="{{ $to }}" required></div>
    <div class="col-md-4"><label class="form-label" for="branch_id">Campus</label><select id="branch_id" name="branch_id" class="form-select"><option value="">All campuses</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-secondary w-100">Apply</button></div>
</form>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total income</div><strong class="fs-5 text-success">{{ \App\Support\Money::format($totalIncome) }}</strong></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total expenses</div><strong class="fs-5 text-danger">{{ \App\Support\Money::format($totalExpenses) }}</strong></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Net operating surplus</div><strong class="fs-5">{{ \App\Support\Money::format($netOperatingSurplus) }}</strong></div></div></div>
</div>
<div class="row g-3">
    @foreach ([['Income', $income, 'success'], ['Expenses', $expenses, 'danger']] as [$heading, $rows, $tone])
        <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-transparent"><strong>{{ $heading }}</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr></thead><tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row['account']->code }}</td><td>{{ $row['account']->name }}</td><td class="text-end text-{{ $tone }}">{{ \App\Support\Money::format($row['amount']) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No posted {{ strtolower($heading) }} for this period.</td></tr>
            @endforelse
            </tbody></table></div></div></div>
    @endforeach
</div>
@endsection