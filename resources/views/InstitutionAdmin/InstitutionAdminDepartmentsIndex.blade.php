@extends('Layouts.LayoutApp')
@section('title', 'Departments')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Departments</h3>
        <small class="text-muted">Manage academic departments and faculties.</small>
    </div>
    @can('create', App\Models\Department::class)
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Department</a>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Programs</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($departments as $dept)
                <tr>
                    <td>{{ $dept->name }}</td>
                    <td><span class="badge bg-secondary">{{ $dept->code }}</span></td>
                    <td>{{ $dept->programs_count }}</td>
                    <td>
                        @if ($dept->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('update', $dept)
                            <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $dept)
                            <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" class="d-inline" onsubmit="return confirm('Delete department {{ $dept->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No departments defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $departments->links('pagination::bootstrap-5') }}</div>
@endsection
