@extends('Layouts.LayoutApp')
@section('title', 'Users')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0 text-body fw-bold">User Management</h3>
        <small class="text-muted">Create, search, and manage system users across all roles and campuses.</small>
    </div>
    @can('create', App\Models\User::class)
    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add User
    </button>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<!-- Search & Filter Controls -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end" id="userFilterForm">
            <!-- Search Query -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-search me-1"></i>Search Users
                </label>
                <div class="input-group">
                    <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search by name, email, phone..." autocomplete="off">
                    @if ($search)
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('search').value=''; document.getElementById('userFilterForm').submit();" title="Clear search">
                            <i class="bi bi-x"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Role Filter -->
            <div class="col-6 col-md-3 col-lg-2">
                <label for="role" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-person-badge me-1"></i>Role
                </label>
                <select id="role" name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->name }}" @selected(($roleFilter ?? '') === $r->name)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Campus / Branch Filter -->
            <div class="col-6 col-md-3 col-lg-3">
                <label for="branch_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-buildings me-1"></i>Campus / Branch
                </label>
                <select id="branch_id" name="branch_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Campuses</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(($branchFilter ?? '') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2 col-lg-2">
                <label for="status" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-toggle-on me-1"></i>Status
                </label>
                <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="1" @selected(($statusFilter ?? '') === '1')>Active Only</option>
                    <option value="0" @selected(($statusFilter ?? '') === '0')>Inactive Only</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-6 col-md-2 col-lg-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                @if ($search || $roleFilter || $branchFilter || ($statusFilter !== null && $statusFilter !== ''))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-footer bg-transparent py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            <i class="bi bi-people-fill me-1 text-primary"></i>Total matching: <strong>{{ $users->total() }}</strong> users
        </span>
        @if ($search || $roleFilter || $branchFilter || ($statusFilter !== null && $statusFilter !== ''))
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                Filtered results active
            </span>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Campus / Branch</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
                                 style="width: 34px; height: 34px; background: linear-gradient(135deg, #0ea5e9, #6366f1); font-size: 0.85rem;">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold text-body">{{ $user->name }}</div>
                                @if ($user->phone)
                                    <div class="small text-muted" style="font-size: 0.75rem;"><i class="bi bi-telephone me-1"></i>{{ $user->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><span class="text-secondary small">{{ $user->email }}</span></td>
                    <td>
                        @if ($user->branch)
                            <span class="badge bg-body-secondary text-body border border-secondary-subtle">
                                <i class="bi bi-buildings me-1"></i>{{ $user->branch->name }}
                            </span>
                        @else
                            <span class="badge bg-body-secondary text-muted border border-secondary-subtle">
                                <i class="bi bi-globe me-1"></i>All Campuses
                            </span>
                        @endif
                    </td>
                    <td>
                        @forelse ($user->roles as $assignedRole)
                            @php
                                $roleBadge = match(strtolower($assignedRole->name)) {
                                    'super admin' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
                                    'institution admin' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                    'teacher' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                    'accountant' => 'bg-success-subtle text-success-emphasis border-success-subtle',
                                    'registrar' => 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
                                    default => 'bg-secondary-subtle text-body border-secondary-subtle',
                                };
                            @endphp
                            <span class="badge {{ $roleBadge }} border">{{ $assignedRole->name }}</span>
                        @empty
                            <span class="badge bg-secondary-subtle text-body border">{{ ucfirst($user->role) }}</span>
                        @endforelse
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('view', $user)
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary" title="Edit User"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $user)
                            @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Deactivate user {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger" title="Deactivate"><i class="bi bi-person-x me-1"></i>Deactivate</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-person-x fs-2 d-block mb-2 text-muted"></i>
                        No users found matching your search and filter criteria.
                        @if ($search || $roleFilter || $branchFilter || ($statusFilter !== null && $statusFilter !== ''))
                            <div class="mt-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
                                </a>
                            </div>
                        @endif
                    </td>
                </tr>
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
                            <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) required value="{{ old('name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) required value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) required minlength="8" maxlength="32">
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('roles')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @error('roles.0')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Campus</label>
                            <select name="branch_id" @class(['form-select', 'is-invalid' => $errors->has('branch_id')]) @required($branches->isNotEmpty())>
                                <option value="">Global / not assigned</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
