@extends('Layouts.LayoutApp')
@section('title', 'HR employees')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">HR employees</h3><small class="text-muted">Employee profiles and monthly base salary records.</small></div>
    @can('hr.create')<a class="btn btn-primary" href="{{ route('admin.hr.employees.create') }}">Add employee</a>@endcan
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0 mb-3"><div class="card-body">
    <form method="GET" class="row g-2">
        <div class="col-md-8"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Employee number, name, or job title"></div>
        <div class="col-md-4"><button class="btn btn-outline-primary">Search</button> <a class="btn btn-outline-secondary" href="{{ route('admin.hr.employees.index') }}">Clear</a></div>
    </form>
</div></div>
<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Employee</th><th>Position</th><th>Department</th><th>Hire date</th><th>Status</th><th>Monthly base</th><th></th></tr></thead>
        <tbody>
        @forelse ($employees as $employee)
            <tr>
                <td><strong>{{ $employee->employee_no }}</strong><div>{{ $employee->user?->name ?? $employee->teacher?->user?->name ?? 'No linked account' }}</div></td>
                <td>{{ $employee->job_title }}</td><td>{{ $employee->department?->name ?? '—' }}</td>
                <td>{{ $employee->hire_date->format('Y-m-d') }}</td><td><span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($employee->employment_status)) }}</span></td>
                <td>{{ \App\Support\Money::format($employee->base_salary) }}</td>
                <td class="text-end">
                    @can('hr.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.hr.employees.edit', $employee) }}">Edit</a>@endcan
                    @can('hr.delete')
                    <form class="d-inline" method="POST" action="{{ route('admin.hr.employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee profile?')">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                    @endcan
                </td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-4">No employee records found.</td></tr>@endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $employees->links('pagination::bootstrap-5') }}</div>
@endsection
