@extends('Layouts.LayoutApp')
@section('title', 'Programs')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Programs</h3>
        <small class="text-muted">Manage academic programs and courses of study.</small>
    </div>
    @can('create', App\Models\Program::class)
    <a href="{{ route('admin.programs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Program</a>
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
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($programs as $program)
                <tr>
                    <td>{{ $program->name }}</td>
                    <td><span class="badge bg-secondary">{{ $program->code }}</span></td>
                    <td>{{ $program->department->name ?? '—' }}</td>
                    <td>
                        @if ($program->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('update', $program)
                            <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $program)
                            <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" class="d-inline" onsubmit="return confirm('Delete program {{ $program->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No programs defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $programs->links('pagination::bootstrap-5') }}</div>
@endsection
