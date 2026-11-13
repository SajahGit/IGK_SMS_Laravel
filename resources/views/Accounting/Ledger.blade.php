@extends('Layouts.LayoutApp')
@section('title', 'General Ledger')
@section('content')
<div class="mb-3"><h3 class="mb-0">General Ledger</h3><small class="text-muted">Account activity with running balance in {{ $currency }}.</small></div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="account_id" class="form-select" required>
            <option value="">— Select account —</option>
            @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($account && $account->id === $a->id)>{{ $a->code }} — {{ $a->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
    <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
    <div class="col-md-2"><select name="currency" class="form-select">@foreach (config('school.currencies', ['LKR']) as $option)<option value="{{ $option }}" @selected($currency === $option)>{{ $option }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Show</button></div>
</form>
@if ($account)
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Date</th><th>Entry</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse ($lines as $l)
                <tr><td>{{ $l->entry->entry_date->format('d M Y') }}</td>
                    <td><a href="{{ route('admin.journals.show', $l->entry) }}">{{ $l->entry->entry_no }}</a></td>
                    <td>{{ $l->entry->description }}</td>
                    <td class="text-end">{{ (float) $l->debit > 0 ? number_format((float) $l->debit, 2) : '' }}</td>
                    <td class="text-end">{{ (float) $l->credit > 0 ? number_format((float) $l->credit, 2) : '' }}</td>
                    <td class="text-end">{{ number_format((float) $l->running, 2) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No postings for this account.</td></tr>
            @endforelse
            </tbody>
            <tfoot class="table-light fw-semibold"><tr><td colspan="5">Closing balance</td><td class="text-end">{{ number_format((float) $running, 2) }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endif
@endsection
