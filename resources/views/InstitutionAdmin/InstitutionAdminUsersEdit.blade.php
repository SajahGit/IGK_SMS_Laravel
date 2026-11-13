@extends('Layouts.LayoutApp')
@section('title', 'Edit User')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit User: {{ $user->name }}</h3>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" autocomplete="off">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) required value="{{ old('name', $user->name) }}">
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) required value="{{ old('email', $user->email) }}">
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
                    <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) minlength="8" maxlength="32">
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Roles <span class="text-danger">*</span></label>
                    <select name="roles[]" class="form-select" required multiple>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(in_array($role->name, old('roles', $roleNames), true))>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @if ($user->student)
                        <div class="form-text">This account is linked to a student profile. Keep the Student role to preserve portal access.</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Campus</label>
                    <select name="branch_id" class="form-select" @required($branches->isNotEmpty())>
                        <option value="">Global / not assigned</option>
                        @foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input me-2" type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label mb-0">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input me-2" type="checkbox" name="must_change_password" value="1" {{ old('must_change_password', $user->must_change_password) ? 'checked' : '' }}>
                        <label class="form-check-label mb-0">Force password change on next login</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
