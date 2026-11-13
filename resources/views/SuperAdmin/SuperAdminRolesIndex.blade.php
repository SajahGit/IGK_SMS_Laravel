@extends('Layouts.LayoutApp')
@section('title', 'Roles & Permissions')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Roles & Permissions</h3>
        <small class="text-muted">Manage system roles and their assigned permissions.</small>
    </div>
    @can('create', Spatie\Permission\Models\Role::class)
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
        <i class="bi bi-plus-lg me-1"></i> Add Role
    </button>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle role-permissions-table">
            <thead class="table-light">
                <tr>
                    <th>Role</th>
                    <th>Guard</th>
                    <th>Permissions</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($roles as $role)
                <tr>
                    <td class="role-name">
                        <div class="fw-semibold">{{ $role->name }}</div>
                    </td>
                    <td class="role-guard"><span class="badge bg-secondary">{{ $role->guard_name }}</span></td>
                    <td class="role-permissions">
                        @forelse ($role->permissions as $perm)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $perm->name }}</span>
                        @empty
                            <span class="text-muted">—</span>
                        @endforelse
                    </td>
                    <td class="role-actions text-end">
                        <div class="btn-group btn-group-sm">
                            @can('update', $role)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $role)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No roles defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Registrar" value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Guard Name</label>
                        <select name="guard_name" class="form-select">
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
