@extends('Layouts.LayoutApp')
@section('title', ($isSelf ? 'My Profile & Salary' : 'Teacher Profile - ' . $teacher->user->name))
@section('content')

@php
    $campusName = $teacher->branch?->name ?? $teacher->user->branch?->name ?? 'Main Campus';
    $campusCode = $teacher->branch?->code ?? $teacher->user->branch?->code ?? 'MAIN';
    $latestEntry = $payrollEntries->first();
    $totalDeductionsSum = $payrollDeductions->sum('amount');
    $pendingDeductionsSum = $payrollDeductions->where('status', 'pending')->sum('amount');
    $appliedDeductionsSum = $payrollDeductions->where('status', 'applied')->sum('amount');
    $availableBranches = ($branches ?? collect())->where('id', '!=', $teacher->branch_id ?? $teacher->user->branch_id);
@endphp

<!-- Header Banner -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-lg bg-primary text-white fs-2 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-weight: 700;">
                    {{ strtoupper(substr($teacher->user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="mb-1 d-flex flex-wrap align-items-center gap-2">
                        {{ $teacher->user->name }}
                        <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : ($teacher->status === 'on_leave' ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">
                            {{ ucfirst(str_replace('_', ' ', $teacher->status)) }}
                        </span>
                        @if ($isSelf)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                <i class="bi bi-person-check-fill me-1"></i>My Staff Portal
                            </span>
                        @endif
                    </h3>
                    <div class="text-muted small d-flex flex-wrap align-items-center gap-2 mt-1">
                        <span><i class="bi bi-building text-primary me-1"></i>Campus: <strong>{{ $campusName }} ({{ $campusCode }})</strong></span>
                        <span>&bull;</span>
                        <span><i class="bi bi-diagram-3 text-secondary me-1"></i>Department: <strong>{{ $teacher->department?->name ?? 'General Faculty' }}</strong></span>
                        <span>&bull;</span>
                        <span><i class="bi bi-person-badge text-info me-1"></i>Employee No: <strong>{{ $employee?->employee_no ?? 'EMP-' . str_pad($teacher->id, 4, '0', STR_PAD_LEFT) }}</strong></span>
                        <span>&bull;</span>
                        <span><i class="bi bi-envelope me-1"></i>{{ $teacher->user->email }}</span>
                        @if ($teacher->phone)
                            <span>&bull;</span>
                            <span><i class="bi bi-telephone me-1"></i>{{ $teacher->phone }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap gap-2">
                @if ($isSelf)
                    <a href="{{ route('teacher.timetable.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar-week me-1"></i> My Timetable
                    </a>
                    <a href="{{ route('teacher.classes.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-easel2 me-1"></i> My Classes
                    </a>
                    <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-clipboard2-check me-1"></i> Mark Attendance
                    </a>
                @else
                    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Teachers
                    </a>
                    @if (auth()->user()?->can('staff.update') || auth()->user()?->isSuperAdmin())
                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#transferTeacherModal">
                            <i class="bi bi-arrow-left-right me-1"></i> Transfer Campus
                        </button>
                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil-square me-1"></i> Edit Teacher
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@include('Layouts.LayoutErrors')

<!-- Key Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">
                    <i class="bi bi-cash-stack text-success me-1"></i>Base Monthly Salary
                </div>
                <div class="fs-4 fw-bold text-dark">
                    @if ($employee && $employee->base_salary)
                        Rs. {{ number_format((float) $employee->base_salary, 2) }}
                    @else
                        <span class="text-muted fs-6">Pending HR Setup</span>
                    @endif
                </div>
                <div class="small text-muted mt-1">
                    Designation: {{ $employee?->job_title ?? 'Teacher' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">
                    <i class="bi bi-scissors text-danger me-1"></i>Super Admin Deductions
                </div>
                <div class="fs-4 fw-bold {{ $totalDeductionsSum > 0 ? 'text-danger' : 'text-secondary' }}">
                    -Rs. {{ number_format($totalDeductionsSum, 2) }}
                </div>
                <div class="small text-muted mt-1 d-flex justify-content-between">
                    <span>Applied: Rs. {{ number_format($appliedDeductionsSum, 2) }}</span>
                    @if ($pendingDeductionsSum > 0)
                        <span class="badge bg-warning text-dark">Pending: Rs. {{ number_format($pendingDeductionsSum, 2) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">
                    <i class="bi bi-calendar-x text-warning me-1"></i>Unpaid Leave Days
                </div>
                <div class="fs-4 fw-bold text-dark">
                    {{ $unpaidLeaveCount }} <span class="fs-6 fw-normal text-muted">day{{ $unpaidLeaveCount != 1 ? 's' : '' }}</span>
                </div>
                <div class="small text-muted mt-1">
                    Automatic base / 30 day deduction rate
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">
                    <i class="bi bi-book text-primary me-1"></i>Teaching Allocations
                </div>
                <div class="fs-4 fw-bold text-primary">
                    {{ $teacher->classSubjects->count() }} <span class="fs-6 fw-normal text-muted">classes / subjects</span>
                </div>
                <div class="small text-muted mt-1">
                    Campus: {{ $campusName }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 w-100" id="teacherProfileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary-pane" type="button" role="tab" aria-selected="true">
            <i class="bi bi-cash-coin me-1 text-success"></i> Salary & Super Admin Deductions
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic-pane" type="button" role="tab" aria-selected="false">
            <i class="bi bi-mortarboard me-1 text-primary"></i> Teaching Allocations & Campus
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves-pane" type="button" role="tab" aria-selected="false">
            <i class="bi bi-calendar-week me-1 text-info"></i> Leaves & Attendance
        </button>
    </li>
</ul>

<!-- Tab Content Panes -->
<div class="tab-content" id="teacherProfileTabsContent">

    <!-- Tab 1: Salary & Super Admin Deductions -->
    <div class="tab-pane fade show active" id="salary-pane" role="tabpanel" aria-labelledby="salary-tab">

        <!-- Super Admin Itemized Deductions (Connected Staff Visibility) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-danger d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill"></i> Super Admin Salary Deductions Log
                    </h5>
                    <div class="text-muted small">
                        Complete transparency for authorized administrative adjustments, advances, penalties, and deductions.
                    </div>
                </div>
                @if (auth()->user()?->isSuperAdmin())
                    <a href="{{ route('admin.salary-deductions.index', ['employee_id' => $employee?->id]) }}" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Manage Deductions
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date / Month</th>
                                <th>Deduction Type</th>
                                <th>Amount</th>
                                <th>Reason & Details</th>
                                <th>Status</th>
                                <th>Authorized By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payrollDeductions as $deduction)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $deduction->effective_date ? $deduction->effective_date->format('d M Y') : '—' }}</div>
                                        <div class="small text-muted">Period: {{ $deduction->payroll_month ?: ($deduction->payrollEntry?->run?->period_start?->format('Y-m') ?? '—') }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $typeBadge = match ($deduction->type) {
                                                'leave_unpaid' => 'bg-warning text-dark',
                                                'advance' => 'bg-info text-dark',
                                                'damage', 'equipment' => 'bg-danger text-white',
                                                'fine', 'disciplinary', 'penalty' => 'bg-danger text-white',
                                                default => 'bg-secondary text-white',
                                            };
                                            $typeLabel = match ($deduction->type) {
                                                'leave_unpaid' => 'Automatic Attendance',
                                                'advance' => 'Salary Advance',
                                                'disciplinary', 'penalty' => 'Disciplinary Fine',
                                                'equipment' => 'Equipment Damage',
                                                default => ucfirst(str_replace('_', ' ', $deduction->type)),
                                            };
                                        @endphp
                                        <span class="badge {{ $typeBadge }} text-uppercase">
                                            {{ $typeLabel }}
                                        </span>
                                        <div class="small text-muted mt-1">
                                            {{ $deduction->type === 'leave_unpaid' ? 'Attendance Calculated' : 'Manual Custom Amount' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger fs-6">
                                            -Rs. {{ number_format((float) $deduction->amount, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $deduction->reason }}</div>
                                        @if ($deduction->notes)
                                            <div class="small text-muted fst-italic">{{ $deduction->notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($deduction->status === 'applied')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check2-circle me-1"></i>Applied to Payroll
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending Next Run
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">
                                            <i class="bi bi-shield-check text-primary me-1"></i>
                                            {{ $deduction->creator?->name ?? 'Super Admin' }}
                                        </div>
                                        <div class="small text-muted">{{ $deduction->created_at?->format('d M Y, h:i A') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-shield-check text-success fs-3 d-block mb-1"></i>
                                        No salary deductions have been recorded for this staff member.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly Payslips History -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-receipt text-primary"></i> Monthly Salary & Payslip History
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payroll Period</th>
                                <th>Base Salary</th>
                                <th>Allowance</th>
                                <th>Statutory (EPF/APIT)</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th class="text-end" style="min-width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payrollEntries as $entry)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $entry->run?->period_start ? $entry->run->period_start->format('F Y') : 'Payroll #' . $entry->id }}
                                        <div class="small text-muted">
                                            @if ($entry->run?->period_start && $entry->run?->period_end)
                                                {{ $entry->run->period_start->format('d M') }} — {{ $entry->run->period_end->format('d M Y') }}
                                            @else
                                                Period {{ $entry->created_at?->format('F Y') ?? '—' }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>Rs. {{ number_format((float) $entry->base_salary, 2) }}</td>
                                    <td class="text-success">+Rs. {{ number_format((float) $entry->allowance, 2) }}</td>
                                    <td class="text-muted small">
                                        <div>EPF 8%: Rs. {{ number_format((float) $entry->employee_epf, 2) }}</div>
                                        @if ((float) $entry->apit_deduction > 0)
                                            <div>APIT: Rs. {{ number_format((float) $entry->apit_deduction, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-danger fw-semibold">
                                        -Rs. {{ number_format((float) $entry->deduction, 2) }}
                                        @if ((float) $entry->manual_deduction > 0)
                                            <div class="small text-muted fst-italic">incl. Rs. {{ number_format((float) $entry->manual_deduction, 2) }} manual</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success fs-6">
                                            Rs. {{ number_format((float) $entry->net_pay, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $runStatus = strtolower($entry->run?->status ?? 'draft');
                                            $badgeClass = match ($runStatus) {
                                                'paid' => 'bg-success',
                                                'approved' => 'bg-info text-dark',
                                                default => 'bg-warning text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ strtoupper($runStatus) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($isSelf)
                                            <a href="{{ route('teacher.payslip', $entry) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-printer me-1"></i>View Payslip
                                            </a>
                                        @else
                                            <a href="{{ route('admin.hr.payroll.entries.payslip', $entry) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-printer me-1"></i>View Payslip
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-wallet2 fs-3 d-block mb-1"></i>
                                        No payroll records generated yet for this employee.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Tab 2: Teaching Allocations & Campus -->
    <div class="tab-pane fade" id="academic-pane" role="tabpanel" aria-labelledby="academic-tab">
        <div class="row g-4">
            <!-- Teaching Subjects -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-mortarboard text-primary"></i> Assigned Classes & Subjects
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Class & Section</th>
                                        <th>Subject</th>
                                        <th>Credit Hours</th>
                                        <th>Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($teacher->classSubjects as $assignment)
                                        <tr>
                                            <td class="fw-semibold">
                                                <i class="bi bi-building me-1 text-muted"></i>
                                                {{ $assignment->schoolClass?->full_name ?? '—' }}
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $assignment->subject?->name ?? '—' }}</div>
                                                <div class="small text-muted">Code: <code>{{ $assignment->subject?->code ?? '—' }}</code></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $assignment->subject?->credit_hours ?? 3 }} hrs
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $assignment->subject?->department?->name ?? $teacher->department?->name ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No class and subject allocations currently assigned to this teacher.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campus & Branch Transfer History -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left-right text-warning"></i> Campus Transfer History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-3 rounded bg-light mb-3 border">
                            <div class="text-muted small text-uppercase fw-semibold">Current Active Campus</div>
                            <div class="fs-5 fw-bold text-primary mt-1">
                                <i class="bi bi-building me-1"></i>{{ $campusName }} ({{ $campusCode }})
                            </div>
                        </div>

                        <div class="timeline">
                            @forelse ($teacher->branchTransfers as $transfer)
                                <div class="border-start border-2 border-warning ps-3 pb-3 position-relative">
                                    <div class="fw-semibold text-dark">
                                        {{ $transfer->fromBranch?->name ?? 'Initial Campus' }} &rarr; <span class="text-success">{{ $transfer->toBranch?->name }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $transfer->transfer_date?->format('d M Y') }}
                                        &bull; Authorized by: {{ $transfer->actor?->name ?? 'System' }}
                                    </div>
                                    @if ($transfer->reason)
                                        <div class="small text-secondary mt-1 bg-white p-2 rounded border">
                                            {{ $transfer->reason }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-muted text-center py-3">
                                    <i class="bi bi-clock-history fs-3 d-block mb-1"></i>
                                    No branch transfer records found. The teacher has been stationed at {{ $campusName }}.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Leaves & Attendance -->
    <div class="tab-pane fade" id="leaves-pane" role="tabpanel" aria-labelledby="leaves-tab">
        <div class="row g-4">
            <!-- Leave Requests History -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-calendar2-range text-info"></i> Leave History & Unpaid Leaves
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dates</th>
                                        <th>Leave Type</th>
                                        <th>Duration</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($leaveRequests as $leave)
                                        @php
                                            $days = $leave->start_date && $leave->end_date ? ($leave->start_date->diffInDays($leave->end_date) + 1) : 1;
                                            $isUnpaid = in_array($leave->leave_type, ['unpaid', 'unpaid_leave', 'loss_of_pay', 'lop']);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $leave->start_date?->format('d M Y') }}
                                                @if ($leave->end_date && $leave->end_date->gt($leave->start_date))
                                                    <div class="small text-muted">&rarr; {{ $leave->end_date->format('d M Y') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $isUnpaid ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-dark border' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}
                                                    @if ($isUnpaid) (Salary Deductible) @endif
                                                </span>
                                            </td>
                                            <td>{{ $days }} day{{ $days > 1 ? 's' : '' }}</td>
                                            <td>
                                                <div class="small">{{ $leave->reason }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $leaveStatusBadge = match ($leave->status) {
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-warning text-dark',
                                                    };
                                                @endphp
                                                <span class="badge {{ $leaveStatusBadge }}">
                                                    {{ ucfirst($leave->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                No leave requests recorded for this staff member.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-success"></i> Recent Staff Attendance Log
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attendances as $att)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ \Carbon\Carbon::parse($att->attendance_date)->format('d M Y, D') }}
                                            </td>
                                            <td>
                                                @php
                                                    $attBadge = match (strtolower($att->status)) {
                                                        'present' => 'bg-success',
                                                        'late' => 'bg-warning text-dark',
                                                        'half_day' => 'bg-info text-dark',
                                                        'absent' => 'bg-danger',
                                                        default => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $attBadge }}">
                                                    {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ $att->notes ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                No attendance records logged for this teacher yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@if (! $isSelf && (auth()->user()?->can('staff.update') || auth()->user()?->isSuperAdmin()))
    <!-- Campus Transfer Modal for Admin -->
    <div class="modal fade" id="transferTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.teachers.transfer', $teacher) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Transfer Teacher Campus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Current Campus</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $campusName }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Destination Campus / Branch</label>
                            <select name="to_branch_id" class="form-select form-select-sm" required>
                                <option value="">Select destination campus...</option>
                                @foreach ($availableBranches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Effective Transfer Date</label>
                            <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Transfer Reason / Remarks</label>
                            <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="e.g. Faculty redistribution, Campus promotion, Relocation"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-arrow-left-right me-1"></i>Confirm Campus Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection
