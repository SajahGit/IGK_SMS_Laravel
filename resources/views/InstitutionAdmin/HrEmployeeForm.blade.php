@extends('Layouts.LayoutApp')
@section('title', $employee ? 'Edit employee' : 'Add employee')
@section('content')
<div class="mb-3"><h3 class="mb-0">{{ $employee ? 'Edit employee' : 'Add employee' }}</h3><small class="text-muted">Salary details are restricted to authorized HR and payroll users.</small></div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $employee ? route('admin.hr.employees.update', $employee) : route('admin.hr.employees.store') }}">
        @csrf @if($employee) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Employee number <span class="text-danger">*</span></label><input class="form-control" name="employee_no" data-uppercase-filter="code" pattern="[A-Z0-9\x2d]+" value="{{ old('employee_no', $employee?->employee_no) }}" maxlength="25" required></div>
            <div class="col-md-4"><label class="form-label">Linked account (optional)</label><select class="form-select" name="user_id"><option value="">No account</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id', $employee?->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Teacher profile (optional)</label><select class="form-select" name="teacher_id"><option value="">Not a teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $employee?->teacher_id) == $teacher->id)>{{ $teacher->user?->name ?? 'Teacher #'.$teacher->id }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Job title <span class="text-danger">*</span></label><input class="form-control" name="job_title" value="{{ old('job_title', $employee?->job_title) }}" maxlength="60" pattern="[a-zA-Z\s\x2d]+" required></div>
            <div class="col-md-4"><label class="form-label">Department</label><select class="form-select" name="department_id" data-searchable-select><option value="">No department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id', $employee?->department_id) == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Campus @if($branches->isNotEmpty())<span class="text-danger">*</span>@endif</label><select class="form-select" name="branch_id" @required($branches->isNotEmpty())><option value="">Select campus</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $employee?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Hire date <span class="text-danger">*</span></label><input class="form-control" type="date" name="hire_date" value="{{ old('hire_date', $employee?->hire_date?->format('Y-m-d')) }}" required></div>
            <div class="col-md-4"><label class="form-label">Employment status <span class="text-danger">*</span></label><select class="form-select" name="employment_status" required>@foreach(['active', 'on_leave', 'terminated'] as $status)<option value="{{ $status }}" @selected(old('employment_status', $employee?->employment_status ?? 'active') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Monthly base salary (LKR) <span class="text-danger">*</span></label><input class="form-control" type="number" step="0.01" min="0" name="base_salary" value="{{ old('base_salary', $employee?->base_salary ?? '0.00') }}" required></div>
            <div class="col-md-4"><label class="form-label">Sri Lankan APIT treatment <span class="text-danger">*</span></label><select class="form-select" name="apit_mode" required>@foreach(['primary' => 'Primary employment (IRD bands)', 'secondary' => 'Secondary employment', 'exempt' => 'Exempt', 'manual' => 'Manual APIT amount'] as $mode => $label)<option value="{{ $mode }}" @selected(old('apit_mode', $employee?->apit_mode ?? 'primary') === $mode)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">Save employee</button> <a class="btn btn-outline-secondary" href="{{ route('admin.hr.employees.index') }}">Cancel</a></div>
    </form>
</div></div>
@endsection
