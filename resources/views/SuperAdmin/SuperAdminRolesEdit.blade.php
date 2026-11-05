@extends('Layouts.LayoutApp')
@section('title', 'Edit Role')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit Role: {{ $role->name }}</h3>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf @method('PUT')
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $role->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guard Name</label>
                    <select name="guard_name" class="form-select">
                        <option value="web" {{ old('guard_name', $role->guard_name) === 'web' ? 'selected' : '' }}>web</option>
                        <option value="api" {{ old('guard_name', $role->guard_name) === 'api' ? 'selected' : '' }}>api</option>
                    </select>
                </div>
            </div>

            <h6 class="mt-4 mb-3">Permissions</h6>
            <div class="row g-3">
                @foreach ($permissions as $permission)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                            {{ $rolePermissions->contains($permission->name) ? 'checked' : '' }}
                            id="perm_{{ $permission->id }}">
                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                            {{ $permission->name }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Role</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
