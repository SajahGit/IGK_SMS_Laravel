@extends('Layouts.LayoutApp')
@section('title', 'Trial Balance')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Trial Balance</h3><small class="text-muted">Posted entries only.</small></div>
    <form method="GET" class="d-flex gap-2">
        <select name="currency" class="form-select">@foreach (config('school.currencies', ['LKR']) as $option)<option value="{{ $option }}" @selected($currency === $option)>{{ $option }}</option>@endforeach</select>
        <input type="date" name="to" class="form-control" value="{{ $to }}">
        <button class="btn btn-outline-secondary">As at</button>
    </form>
</div>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit ({{ $currency }})</th><th class="text-end">Credit ({{ $currency }})</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr><td><span class="badge bg-secondary">{{ $r['account']->code }}</span></td>
                    <td><a href="{{ route('admin.accounting.ledger', ['account_id' => $r['account']->id]) }}">{{ $r['account']->name }}</a></td>
                    <td>{{ App\Models\Account::TYPES[$r['account']->type] }}</td>
                    <td class="text-end">{{ bccomp($r['debit'], '0', 2) ? \App\Support\Money::format($r['debit'], $currency.' ') : '' }}</td>
                    <td class="text-end">{{ bccomp($r['credit'], '0', 2) ? \App\Support\Money::format($r['credit'], $currency.' ') : '' }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No postings yet.</td></tr>
            @endforelse
            </tbody>
            <tfoot class="table-light fw-semibold"><tr><td colspan="3">Total</td>
                <td class="text-end">{{ \App\Support\Money::format($totalDebit, $currency.' ') }}</td>
                <td class="text-end">{{ \App\Support\Money::format($totalCredit, $currency.' ') }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endsection
