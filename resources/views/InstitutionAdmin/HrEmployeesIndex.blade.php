@extends('Layouts.LayoutApp')
@section('title', 'HR employees')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">HR Employees</h3>
        <small class="text-muted">Staff and employee profiles, campus assignments, and base salaries.</small>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('admin.hr.payroll.index') }}">
            <i class="bi bi-cash-stack me-1"></i> Payroll & Deductions
        </a>
        <a class="btn btn-outline-secondary" href="{{ route('admin.hr.leaves.index') }}">
            <i class="bi bi-calendar2-check me-1"></i> Leaves
        </a>
        @can('hr.create')
            <a class="btn btn-primary" href="{{ route('admin.hr.employees.create') }}">
                <i class="bi bi-person-plus-fill me-1"></i> Add Employee
            </a>
        @endcan
    </div>
</div>

<div class="alert alert-info py-2 px-3 small shadow-sm d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
    <div class="flex-grow-1">
        <strong>Salary Deduction Guide:</strong> Unpaid approved leaves are deducted automatically during monthly payroll calculation (at daily rate <code>base / 30</code>). Custom/manual salary deductions (advances, penalties, equipment) can be applied or adjusted inside any <em>Draft Payroll Run</em> via <strong>Deductions & Pay</strong>.
    </div>
    <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-sm btn-primary">Go to Payroll</a>
</div>

@include('Layouts.LayoutErrors')

<!-- Category Tabs -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.hr.employees.index', array_filter(['search' => request('search'), 'branch_id' => $branchFilter, 'department_id' => $departmentFilter, 'employment_status' => $statusFilter])) }}"
       class="btn btn-sm {{ ($category ?? 'all') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
        All Employees <span class="badge {{ ($category ?? 'all') === 'all' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $totalCount ?? $employees->total() }}</span>
    </a>
    @foreach ($categories as $catKey => $catLabel)
        @if (($categoryCounts[$catKey] ?? 0) > 0 || ($category ?? 'all') === $catKey)
            <a href="{{ route('admin.hr.employees.index', array_filter(['category' => $catKey, 'search' => request('search'), 'branch_id' => $branchFilter, 'department_id' => $departmentFilter, 'employment_status' => $statusFilter])) }}"
               class="btn btn-sm {{ ($category ?? 'all') === $catKey ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $catLabel }} <span class="badge {{ ($category ?? 'all') === $catKey ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $categoryCounts[$catKey] ?? 0 }}</span>
            </a>
        @endif
    @endforeach
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Search</label>
                <input class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Employee no, name, email, or title">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="all" @selected(($category ?? 'all') === 'all')>All Categories</option>
                    @foreach ($categories as $catKey => $catLabel)
                        <option value="{{ $catKey }}" @selected(($category ?? 'all') === $catKey)>{{ $catLabel }} ({{ $categoryCounts[$catKey] ?? 0 }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Campus / Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Campuses</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($branchFilter ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" @selected((string) ($departmentFilter ?? '') === (string) $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="employment_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(($statusFilter ?? '') === 'active')>Active</option>
                    <option value="on_leave" @selected(($statusFilter ?? '') === 'on_leave')>On Leave</option>
                    <option value="terminated" @selected(($statusFilter ?? '') === 'terminated')>Terminated</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.hr.employees.index') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Category & Position</th>
                    <th>Campus / Branch</th>
                    <th>Department</th>
                    <th>Hire Date</th>
                    <th>Status</th>
                    <th>Monthly Base</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($employees as $employee)
                @php
                    $empCat = \App\Services\EmployeeSyncService::categoryFor($employee);
                    $empCatLabel = $categories[$empCat] ?? 'General Staff';
                    $empCatBadge = match ($empCat) {
                        'teaching' => 'bg-info-subtle text-info border border-info-subtle',
                        'administration' => 'bg-primary-subtle text-primary border border-primary-subtle',
                        'finance' => 'bg-success-subtle text-success border border-success-subtle',
                        'registrar' => 'bg-warning-subtle text-warning border border-warning-subtle',
                        'library' => 'bg-secondary-subtle text-secondary border',
                        default => 'bg-light text-dark border',
                    };
                @endphp
                <tr>
                    <td>
                        <strong>{{ $employee->employee_no }}</strong>
                        <div class="fw-medium">{{ $employee->user?->name ?? $employee->teacher?->user?->name ?? 'No linked account' }}</div>
                        @if ($employee->user?->email)
                            <div class="small text-muted">{{ $employee->user->email }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $empCatBadge }} mb-1">{{ $empCatLabel }}</span>
                        <div class="small fw-medium">{{ $employee->job_title }}</div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border">
                            <i class="bi bi-building me-1"></i>{{ $employee->branch?->name ?? 'Main Campus' }}
                        </span>
                        @if ($employee->branchTransfers->isNotEmpty())
                            <span class="badge bg-secondary-subtle text-body border small ms-1" title="Has transfer history">
                                <i class="bi bi-arrow-left-right"></i> {{ $employee->branchTransfers->count() }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $employee->department?->name ?? '—' }}</td>
                    <td>{{ $employee->hire_date->format('d M Y') }}</td>
                    <td>
                        @php
                            $empStatusBadge = match($employee->employment_status) {
                                'active' => 'bg-success',
                                'on_leave' => 'bg-warning text-dark',
                                'terminated' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $empStatusBadge }}">{{ str_replace('_', ' ', ucfirst($employee->employment_status)) }}</span>
                    </td>
                    <td class="fw-semibold">{{ \App\Support\Money::format($employee->base_salary) }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('hr.update')
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#empTransferModal{{ $employee->id }}" title="Transfer Employee Campus">
                                    <i class="bi bi-arrow-left-right me-1"></i>Transfer
                                </button>
                                <a class="btn btn-outline-primary" href="{{ route('admin.hr.employees.edit', $employee) }}">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                            @endcan
                            @can('hr.delete')
                                <form class="d-inline" method="POST" action="{{ route('admin.hr.employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee profile?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                </form>
                            @endcan
                        </div>

                        @can('hr.update')
                        <!-- Employee Campus Transfer Modal -->
                        <div class="modal fade text-start" id="empTransferModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.hr.employees.transfer', $employee) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Transfer Employee Campus</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small">Employee</label>
                                                <input class="form-control form-control-sm" value="{{ $employee->employee_no }} - {{ $employee->user?->name ?? $employee->job_title }}" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small">Current Campus</label>
                                                <input class="form-control form-control-sm" value="{{ $employee->branch?->name ?? 'Main Campus' }} ({{ $employee->branch?->code ?? 'MAIN' }})" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small">Destination Campus <span class="text-danger">*</span></label>
                                                <select name="to_branch_id" class="form-select form-select-sm" required>
                                                    <option value="">Select destination campus</option>
                                                    @foreach ($branches->where('id', '!=', $employee->branch_id) as $br)
                                                        <option value="{{ $br->id }}">{{ $br->name }} ({{ $br->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small">Transfer Date <span class="text-danger">*</span></label>
                                                <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small">Reason / Note</label>
                                                <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="e.g. Campus re-assignment, Faculty rotation"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-check-circle me-1"></i>Confirm Transfer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No employee records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $employees->links('pagination::bootstrap-5') }}</div>
@endsection
