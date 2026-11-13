@extends('Layouts.LayoutApp')
@section('title', 'Students')
@section('content')
<div class="page-head">
    <div>
        <h3 class="mb-1">Students</h3>
        <span class="text-muted small">Total matching students: {{ number_format($students->total()) }}</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.students.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add student</a>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or admission no"></div>
    <div class="col-md-3">
        <select name="class_id" class="form-select">
            <option value="">All classes</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="POST" action="{{ route('admin.students.filters.store') }}" class="d-flex gap-2">
        @csrf
        <input type="hidden" name="filters[q]" value="{{ request('q') }}">
        <input type="hidden" name="filters[class_id]" value="{{ request('class_id') }}">
        <input name="name" class="form-control form-control-sm" maxlength="80" placeholder="Name this filter" required>
        <button class="btn btn-sm btn-outline-primary">Save filter</button>
    </form>
    @foreach ($savedFilters as $savedFilter)
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.students.index', $savedFilter->filters) }}">{{ $savedFilter->name }}</a>
        <form method="POST" action="{{ route('admin.students.filters.destroy', $savedFilter) }}">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $savedFilter->name }}">×</button>
        </form>
    @endforeach
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Admission No</th><th>Name</th><th>Email</th><th>Class</th><th>Guardian</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr>
                    <td>{{ $s->admission_no }}</td>
                    <td>{{ $s->user->name }}</td>
                    <td>{{ $s->user->email }}</td>
                    <td>{{ $s->class?->full_name ?? 'Unassigned' }}</td>
                    <td>{{ $s->primaryGuardian->first()?->user?->name ?? '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.students.edit', $s) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                        <form method="POST" action="{{ route('admin.students.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete this student and all attendance and results?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>
@endsection
