@extends('Layouts.LayoutApp')
@section('title', 'Library Loans')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Library Loans</h3><small class="text-muted">All issued books, due dates and returns.</small></div>
    <a href="{{ route('library.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Library Dashboard</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><select name="state" class="form-select">
        <option value="">All loans</option>
        @foreach (['active' => 'Active', 'overdue' => 'Overdue', 'returned' => 'Returned'] as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach
    </select></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>{{ $loan->copy?->book?->title }} <small class="text-muted">{{ $loan->copy?->accession_no }}</small></td>
                    <td>{{ $loan->student?->user?->name }}</td>
                    <td>{{ $loan->issued_at?->format('d M Y') }}</td>
                    <td>
                        @if (! $loan->returned_at)
                        <form method="POST" action="{{ route('library.loans.update', $loan) }}" class="d-flex gap-1">
                            @csrf @method('PUT')
                            <input type="date" name="due_at" value="{{ $loan->due_at?->toDateString() }}" class="form-control form-control-sm">
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg"></i></button>
                        </form>
                        @else {{ $loan->due_at?->format('d M Y') }} @endif
                    </td>
                    <td>
                        @if ($loan->returned_at)<span class="badge bg-success">Returned {{ $loan->returned_at->format('d M') }}</span>
                        @elseif ($loan->due_at && $loan->due_at->isPast())<span class="badge bg-danger">Overdue</span>
                        @else<span class="badge bg-primary">Active</span>@endif
                        @if ($loan->fine)<span class="badge bg-warning text-dark">Fine {{ number_format((float) $loan->fine->amount, 2) }}</span>@endif
                    </td>
                    <td class="text-end">
                        @if (! $loan->returned_at)
                        <form method="POST" action="{{ route('library.loans.return', $loan) }}" class="d-inline">@csrf
                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-box-arrow-in-down me-1"></i>Return</button></form>
                        @else
                        <form method="POST" action="{{ route('library.loans.destroy', $loan) }}" class="d-inline" onsubmit="return confirm('Delete this loan record?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No loans.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $loans->links('pagination::bootstrap-5') }}</div>
@endsection
