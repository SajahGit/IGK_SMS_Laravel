@extends('Layouts.LayoutApp')
@section('title', 'Create Role')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Create Role</h3>
        <small class="text-muted">Add a role, then assign its permissions from the role list.</small>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Back to Roles</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Role name</label>
                <input id="name" name="name" type="text" class="form-control" value="{{ old('name') }}" required maxlength="255">
            </div>
            <div class="mb-3">
                <label for="guard_name" class="form-label">Guard</label>
                <select id="guard_name" name="guard_name" class="form-select" required>
                    <option value="web" @selected(old('guard_name', 'web') === 'web')>web</option>
                    <option value="api" @selected(old('guard_name') === 'api')>api</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Role</button>
        </form>
    </div>
</div>
@endsection
