@extends('Layouts.LayoutApp')
@section('title', 'Users')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">User Management</h3>
        <small class="text-muted">Create and manage system users and their roles.</small>
    </div>
    @can('create', App\Models\User::class)
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-plus-lg me-1"></i> Add User
    </button>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @forelse ($user->roles as $assignedRole)
                            <span class="badge bg-info">{{ $assignedRole->name }}</span>
                        @empty
                            <span class="badge bg-secondary">{{ $user->role }}</span>
                        @endforelse
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('view', $user)
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $user)
                            @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Deactivate user {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Deactivate</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input me-2" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label mb-0">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input me-2" type="checkbox" name="must_change_password" value="1">
                                <label class="form-check-label mb-0">Force password change on next login</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
