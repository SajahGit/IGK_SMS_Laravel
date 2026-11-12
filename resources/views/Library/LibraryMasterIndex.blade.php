@extends('Layouts.LayoutApp')
@section('title', 'Library '.$title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Library {{ $title }}</h3><small class="text-muted">Add, rename and remove {{ strtolower($title) }}.</small></div>
    <a href="{{ route('library.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Library Dashboard</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('library.'.$slug.'.store') }}" class="row g-2">
            @csrf
            <div class="col-md-8"><input type="text" name="name" class="form-control" placeholder="New {{ strtolower($singular) }} name" required maxlength="120"></div>
            <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add {{ $singular }}</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Name</th><th>Books</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('library.'.$slug.'.update', $row) }}" class="d-flex gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $row->name }}" class="form-control form-control-sm" required maxlength="120">
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                        </form>
                    </td>
                    <td>{{ $row->books_count }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('library.'.$slug.'.destroy', $row) }}" class="d-inline" onsubmit="return confirm('Delete {{ $row->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rows->links('pagination::bootstrap-5') }}</div>
@endsection
