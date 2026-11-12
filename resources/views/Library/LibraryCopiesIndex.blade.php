@extends('Layouts.LayoutApp')
@section('title', 'Library Copies')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Library Copies</h3><small class="text-muted">Update condition and status of every physical copy.</small></div>
    <a href="{{ route('library.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Library Dashboard</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><select name="status" class="form-select">
        <option value="">All statuses</option>
        @foreach (['available', 'borrowed', 'lost', 'withdrawn'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Accession</th><th>Book</th><th>Condition</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($copies as $copy)
                <tr>
                                        <td><form method="POST" action="{{ route('library.copies.update', $copy) }}" id="copy-{{ $copy->id }}">@csrf @method('PUT')</form><span class="badge bg-secondary">{{ $copy->accession_no }}</span></td>
                    <td>{{ $copy->book?->title }}</td>
                    <td><select name="condition" form="copy-{{ $copy->id }}" class="form-select form-select-sm">
                        @foreach (['new', 'good', 'fair', 'poor', 'damaged'] as $c)<option value="{{ $c }}" @selected($copy->condition === $c)>{{ ucfirst($c) }}</option>@endforeach
                    </select></td>
                    <td><select name="status" form="copy-{{ $copy->id }}" class="form-select form-select-sm">
                        @foreach (['available', 'borrowed', 'lost', 'withdrawn'] as $s)<option value="{{ $s }}" @selected($copy->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                    </select></td>
                    <td class="text-end">
                        <button form="copy-{{ $copy->id }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                        <form method="POST" action="{{ route('library.copies.destroy', $copy) }}" class="d-inline" onsubmit="return confirm('Delete copy {{ $copy->accession_no }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No copies.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $copies->links('pagination::bootstrap-5') }}</div>
@endsection
