@extends('layouts.LayoutApp')
@section('title', 'Subjects')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Subjects</h3>
    <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add subject</a>
</div>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Name</th><th>Code</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($subjects as $s)
            <tr>
                <td>{{ $s->name }}</td>
                <td>{{ $s->code }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.subjects.edit', $s) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('admin.subjects.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete this subject?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">No subjects yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $subjects->links('pagination::bootstrap-5') }}</div>
@endsection
