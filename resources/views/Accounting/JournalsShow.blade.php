@extends('Layouts.LayoutApp')
@section('title', 'Journal Entry '.$entry->entry_no)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-0">{{ $entry->entry_no }}</h3>
        <small class="text-muted">{{ $entry->entry_date->format('d M Y') }} · {{ $entry->description }} @if ($entry->branch) · {{ $entry->branch->name }} @endif</small></div>
    <a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light"><tr><th>Account</th><th>Memo</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @foreach ($entry->lines as $line)
                <tr><td>{{ $line->account->code }} — {{ $line->account->name }}</td><td>{{ $line->memo }}</td>
                    <td class="text-end">{{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                    <td class="text-end">{{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '' }}</td></tr>
            @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold"><tr><td colspan="2">Total</td>
                <td class="text-end">{{ number_format((float) $entry->lines->sum('debit'), 2) }}</td>
                <td class="text-end">{{ number_format((float) $entry->lines->sum('credit'), 2) }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endsection
