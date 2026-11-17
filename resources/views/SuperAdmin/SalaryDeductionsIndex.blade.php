@extends('Layouts.LayoutApp')
@section('title', 'Salary Deductions Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="mb-0">Salary Deductions Console</h3>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-shield-lock-fill me-1"></i>Super Admin Exclusive</span>
        </div>
        <small class="text-muted">Dedicated governance console for staff salary deductions, advances, disciplinary penalties, and equipment adjustments.</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#policyModal">
            <i class="bi bi-gear-wide-connected me-1"></i>Automatic Deduction Rules
        </button>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createDeductionModal">
            <i class="bi bi-plus-lg me-1"></i>Record Manual Deduction
        </button>
    </div>
</div>

@include('Layouts.LayoutErrors')

<!-- Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'cash-coin', 'value' => \App\Support\Money::format((string) $totalDeductions), 'label' => 'Total Deductions Recorded', 'tone' => 'primary'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'clock-history', 'value' => \App\Support\Money::format((string) $pendingDeductions), 'label' => 'Pending Payroll Attachment', 'tone' => 'warning'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'check-all', 'value' => \App\Support\Money::format((string) $appliedDeductions), 'label' => 'Applied in Payroll Runs', 'tone' => 'success'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'people-fill', 'value' => $staffCount, 'label' => 'Staff Members Impacted', 'tone' => 'info'])
    </div>
</div>

<!-- Deduction Modes Policy Status Card -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge {{ $autoDeductionEnabled ? 'bg-success' : 'bg-secondary' }}">
                        <i class="bi bi-check2-circle me-1"></i>Automatic Attendance Deductions: {{ $autoDeductionEnabled ? 'ACTIVE' : 'DISABLED' }}
                    </span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                        Mode: {{ $autoDeductionMode === 'fixed_amount' ? 'Super Admin Fixed Rate' : 'Pro-Rata Daily Salary (Base / 30)' }}
                    </span>
                </div>
                <div class="small text-muted">
                    @if ($autoDeductionMode === 'fixed_amount')
                        Unpaid leaves & absent days are automatically deducted at a fixed rate of <strong class="text-dark">Rs. {{ number_format($autoDeductionFixedAmount, 2) }} / day</strong>.
                    @else
                        Unpaid leaves & absent days are calculated proportionally per employee (<strong class="text-dark">Monthly Base Salary / 30 calendar days</strong>).
                    @endif
                    Manual deductions (advances, damage, disciplinary penalties) can be recorded anytime with custom wished amounts.
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#policyModal">
                    <i class="bi bi-sliders me-1"></i>Configure Automatic Rules
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-3">
        <select name="employee_id" class="form-select form-select-sm" data-searchable-select>
            <option value="">All Staff Members</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>
                    {{ $emp->employee_no }} &mdash; {{ $emp->user?->name ?? $emp->teacher?->user?->name ?? $emp->job_title }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="branch_id" class="form-select form-select-sm">
            <option value="">All Campuses</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') }}" placeholder="Payroll Month">
    </div>
    <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
            <option value="">All Deduction Types</option>
            <option value="advance" @selected(request('type') === 'advance')>Salary Advance</option>
            <option value="disciplinary" @selected(request('type') === 'disciplinary')>Disciplinary Fine</option>
            <option value="penalty" @selected(request('type') === 'penalty')>Attendance Penalty</option>
            <option value="leave_unpaid" @selected(request('type') === 'leave_unpaid')>Unpaid Leave</option>
            <option value="equipment" @selected(request('type') === 'equipment')>Equipment Damage</option>
            <option value="uniform" @selected(request('type') === 'uniform')>Uniform Fee</option>
            <option value="manual" @selected(request('type') === 'manual')>Manual Administrative</option>
            <option value="other" @selected(request('type') === 'other')>Other</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="{{ route('admin.salary-deductions.index') }}" class="btn btn-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
    </div>
</form>

<!-- Deductions Table -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date / Period</th>
                    <th>Staff Member</th>
                    <th>Campus</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Reason & Details</th>
                    <th>Status</th>
                    <th>Authorized By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deductions as $ded)
                    @php
                        $empUser = $ded->employee?->user ?? $ded->employee?->teacher?->user;
                        $badgeTone = match ($ded->type) {
                            'leave_unpaid' => 'warning text-dark',
                            'advance' => 'info text-dark',
                            'damage' => 'danger text-white',
                            'disciplinary', 'penalty' => 'danger text-white',
                            default => 'secondary text-white',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $ded->effective_date ? $ded->effective_date->format('d M Y') : '—' }}</div>
                            <div class="small text-muted">{{ $ded->payroll_month ?: ($ded->payrollEntry?->run?->period_start?->format('Y-m') ?? '—') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $empUser?->name ?? 'Staff Member' }}</div>
                            <div class="small text-muted"><code>{{ $ded->employee?->employee_no }}</code> &bull; {{ $ded->employee?->job_title }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-building me-1"></i>{{ $ded->employee?->branch?->name ?? 'Main Campus' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $badgeTone }} text-uppercase">
                                {{ str_replace('_', ' ', $ded->type) }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-danger fs-6">
                                -Rs. {{ number_format((float) $ded->amount, 2) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $ded->reason }}</div>
                            @if ($ded->notes)
                                <div class="small text-muted fst-italic">{{ $ded->notes }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($ded->status === 'applied')
                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Deducted in Payroll Run #{{ $ded->payroll_entry_id }}">
                                    <i class="bi bi-check2-circle me-1"></i>Applied
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Will be deducted in next payroll batch">
                                    <i class="bi bi-hourglass-split me-1"></i>Pending
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $ded->creator?->name ?? 'Super Admin' }}</div>
                            <div class="small text-muted">{{ $ded->created_at?->format('d M Y, h:i A') }}</div>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.salary-deductions.destroy', $ded) }}" onsubmit="return confirm('Remove this salary deduction record? If attached to a draft payroll run, net salary will be recalculated.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Deduction">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-scissors fs-2 text-muted d-block mb-2"></i>
                            No salary deductions found matching current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($deductions->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $deductions->links() }}
        </div>
    @endif
</div>

<!-- Modal 1: Automatic Attendance Deduction Policy -->
<div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.salary-deductions.policy.update') }}">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="policyModalLabel">
                            <i class="bi bi-gear-wide-connected text-warning me-2"></i>Automatic Deduction Policy
                        </h5>
                        <small class="text-muted">Super Admin Rule Configuration</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Configure how automatic attendance deductions are computed during monthly payroll batch generation. Super Admin can adjust these settings at any time.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Automatic Attendance Deductions</label>
                        <select name="auto_deduction_enabled" class="form-select form-select-sm" required>
                            <option value="1" @selected($autoDeductionEnabled)>Enabled (Calculate automatically from unpaid leaves and attendance)</option>
                            <option value="0" @selected(! $autoDeductionEnabled)>Disabled (Do not deduct attendance automatically)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deduction Calculation Mode <span class="text-danger">*</span></label>
                        <select name="auto_deduction_mode" class="form-select form-select-sm" required>
                            <option value="pro_rata" @selected($autoDeductionMode === 'pro_rata')>Pro-Rata Daily Salary (Monthly Base Salary / 30 calendar days)</option>
                            <option value="fixed_amount" @selected($autoDeductionMode === 'fixed_amount')>Super Admin Configured Fixed Amount per Absent Day</option>
                        </select>
                        <div class="form-text small text-muted">Choose whether to deduct staff base salary proportionately or apply a fixed daily penalty amount.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fixed Daily Deduction Rate (LKR)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="auto_deduction_fixed_amount" class="form-control form-control-sm" min="0" step="0.01" value="{{ $autoDeductionFixedAmount }}" required>
                        </div>
                        <div class="form-text small text-muted">Applied when calculation mode is set to "Super Admin Fixed Amount".</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Fixed Late Arrival Penalty (LKR)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="auto_deduction_late_penalty" class="form-control form-control-sm" min="0" step="0.01" value="{{ $autoDeductionLatePenalty }}">
                        </div>
                        <div class="form-text small text-muted">Optional penalty per unexcused late attendance incident.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2-circle me-1"></i>Save Policy Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Record Manual Deduction (Custom Wished Amount) -->
<div class="modal fade" id="createDeductionModal" tabindex="-1" aria-labelledby="createDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.salary-deductions.store') }}">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="createDeductionModalLabel">
                            <i class="bi bi-scissors text-danger me-2"></i>Record Manual Salary Deduction
                        </h5>
                        <small class="text-muted">Custom / Wished Amount Entry with Full Transparency</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-shield-check me-1"></i>
                        Manual deductions recorded here will automatically update the staff member's net salary in payroll and will be <strong>immediately visible to the connected staff member</strong> on their personal profile.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Staff Member <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select form-select-sm" required>
                            <option value="">Choose employee / teacher...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->employee_no }} &mdash; {{ $emp->user?->name ?? $emp->teacher?->user?->name ?? $emp->job_title }} ({{ $emp->branch?->name ?? 'Main' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payroll Month <span class="text-danger">*</span></label>
                            <input type="month" name="payroll_month" class="form-control form-control-sm" value="{{ now()->format('Y-m') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Effective Date</label>
                            <input type="date" name="effective_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Deduction Category <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="advance">Salary Advance Repayment</option>
                                <option value="disciplinary">Disciplinary Penalty / Fine</option>
                                <option value="penalty">Attendance / Late Penalty</option>
                                <option value="equipment">Equipment Damage / Lost Item</option>
                                <option value="uniform">Uniform / Materials Fee</option>
                                <option value="manual">General Administrative Deduction</option>
                                <option value="other">Other Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Custom / Wished Amount (LKR) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="amount" class="form-control form-control-sm" min="1" step="0.01" placeholder="e.g. 5000.00" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deduction Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. November Salary Advance, Missing Lab Device, Late Fine" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Internal Notes & Description (Visible to staff)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional background context or reference invoice number"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-check2-circle me-1"></i>Authorize & Post Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
