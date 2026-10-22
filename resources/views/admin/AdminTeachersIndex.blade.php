@extends('layouts.LayoutApp')
@section('title', 'Teachers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Teachers</h3>
    <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add teacher</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
</form>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Teaches</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($teachers as $t)
            <tr>
                <td>{{ $t->user->name }}</td>
                <td>{{ $t->user->email }}</td>
                <td>{{ $t->phone ?? '-' }}</td>
                <td class="small">
                    @forelse ($t->classSubjects as $cs)
                        <span class="badge bg-light text-dark border">{{ $cs->schoolClass->full_name }}: {{ $cs->subject->name }}</span>
                    @empty
                        <span class="text-muted">Not assigned</span>
                    @endforelse
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.teachers.edit', $t) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('admin.teachers.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Delete this teacher?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No teachers found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $teachers->links('pagination::bootstrap-5') }}</div>
@endsection
