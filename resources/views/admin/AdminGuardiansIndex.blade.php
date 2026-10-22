@extends('layouts.LayoutApp')
@section('title', 'Parents')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Parents</h3>
    <a href="{{ route('admin.guardians.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add parent</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
</form>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Children</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($guardians as $g)
            <tr>
                <td>{{ $g->user->name }}</td>
                <td>{{ $g->user->email }}</td>
                <td>{{ $g->phone ?? '-' }}</td>
                <td class="small">{{ $g->students->map(fn ($s) => $s->user->name)->join(', ') ?: '-' }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.guardians.edit', $g) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('admin.guardians.destroy', $g) }}" class="d-inline" onsubmit="return confirm('Delete this parent account?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No parents found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $guardians->links('pagination::bootstrap-5') }}</div>
@endsection
