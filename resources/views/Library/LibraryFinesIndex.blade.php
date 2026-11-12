@extends('Layouts.LayoutApp')
@section('title', 'Library Fines')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Library Fines</h3><small class="text-muted">Edit, collect or waive overdue fines.</small></div>
    <a href="{{ route('library.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Library Dashboard</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Student</th><th>Book</th><th>Amount</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($fines as $fine)
                <tr>
                                        <td><form method="POST" action="{{ route('library.fines.update', $fine) }}" id="fine-{{ $fine->id }}">@csrf @method('PUT')</form>{{ $fine->loan?->student?->user?->name }}</td>
                    <td>{{ $fine->loan?->copy?->book?->title }}</td>
                    @if ($fine->paid_at)
                        <td>{{ number_format((float) $fine->amount, 2) }}</td><td>{{ $fine->reason }}</td>
                        <td><span class="badge bg-success">Paid {{ $fine->paid_at->format('d M Y') }}</span></td><td></td>
                    @else
                        <td style="width:130px"><input type="number" step="0.01" min="0.01" name="amount" form="fine-{{ $fine->id }}" value="{{ $fine->amount }}" class="form-control form-control-sm"></td>
                        <td><input type="text" name="reason" form="fine-{{ $fine->id }}" value="{{ $fine->reason }}" class="form-control form-control-sm" maxlength="160"></td>
                        <td><span class="badge bg-warning text-dark">Unpaid</span></td>
                        <td class="text-end">
                            <button form="fine-{{ $fine->id }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                            <form method="POST" action="{{ route('library.fines.pay', $fine) }}" class="d-inline">@csrf
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-cash me-1"></i>Collect</button></form>
                            <form method="POST" action="{{ route('library.fines.destroy', $fine) }}" class="d-inline" onsubmit="return confirm('Waive this fine?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Waive</button></form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No fines.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $fines->links('pagination::bootstrap-5') }}</div>
@endsection
