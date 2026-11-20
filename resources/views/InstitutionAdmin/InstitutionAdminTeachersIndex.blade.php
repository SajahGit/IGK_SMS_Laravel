@extends('Layouts.LayoutApp')
@section('title', 'Teachers')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Teachers</h3>
        <small class="text-muted">Faculty profiles, campus assignments, and teaching workloads.</small>
    </div>
    @can('staff.create')
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus-fill me-1"></i>Add Teacher</a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-3">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name or email">
    </div>
    <div class="col-md-3">
        <select name="branch_id" class="form-select">
            <option value="">All Campuses</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach ($departments as $dept)
                <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            <option value="on_leave" @selected(request('status') === 'on_leave')>On Leave</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
    </div>
</form>

@include('components.teacher-table', ['teachers' => $teachers, 'branches' => $branches])
@endsection
