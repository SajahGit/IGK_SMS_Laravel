@extends('Layouts.LayoutApp')
@section('title', 'Payroll Details')
@section('content')
@php
    $autoMode = \App\Models\Setting::get('payroll.auto_deduction_mode', 'pro_rata');
    $fixedAmount = (float) \App\Models\Setting::get('payroll.auto_deduction_fixed_amount', 2000.00);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-wallet2 text-primary"></i>
            Payroll Period: {{ $payrollRun->period_start->format('d M Y') }} &ndash; {{ $payrollRun->period_end->format('d M Y') }}
        </h3>
        <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
            <span>Branch: <strong>{{ $payrollRun->branch?->name ?? 'Main Campus' }}</strong></span>
            <span>&bull;</span>
            <span>Status: 
                <span class="badge {{ $payrollRun->status === 'paid' ? 'bg-success' : ($payrollRun->status === 'approved' ? 'bg-info text-dark' : 'bg-warning text-dark') }}">
                    {{ ucfirst($payrollRun->status) }}
                </span>
            </span>
            <span>&bull;</span>
            <span>Total Staff: <strong>{{ $payrollRun->entries->count() }}</strong></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.hr.payroll.index') }}">
            <i class="bi bi-arrow-left me-1"></i> Back to Payroll Runs
        </a>
    </div>
</div>

@include('Layouts.LayoutErrors')
@if (session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Base Salary</th>
                    <th>Allowance</th>
                    <th>Leave Deduction (Unpaid)</th>
                    <th>Manual Deduction</th>
                    <th>EPF (8%)</th>
                    <th>APIT Tax</th>
                    <th>Net Pay</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payrollRun->entries as $entry)
                <tr>
                    <td>
                        <div class="fw-semibold text-body">
                            {{ $entry->employee->user?->name ?? $entry->employee->teacher?->user?->name ?? $entry->employee->job_title }}
                        </div>
                        <div class="small text-muted d-flex align-items-center gap-1">
                            <code>{{ $entry->employee->employee_no }}</code>
                            <span>&bull;</span>
                            <span>{{ $entry->employee->designation ?? $entry->employee->job_title }}</span>
                        </div>
                    </td>
                    <td>{{ \App\Support\Money::format($entry->base_salary) }}</td>
                    <td>
                        @if ((float) $entry->allowance > 0)
                            <span class="text-success fw-semibold">+{{ \App\Support\Money::format($entry->allowance) }}</span>
                        @else
                            <span class="text-muted">0.00</span>
                        @endif
                    </td>
                    <td>
                        @if ($entry->unpaid_leave_days > 0)
                            <span class="text-danger fw-semibold">-{{ \App\Support\Money::format($entry->leave_deduction) }}</span>
                            <div class="small text-muted">({{ $entry->unpaid_leave_days }} unpaid day{{ $entry->unpaid_leave_days > 1 ? 's' : '' }})</div>
                        @else
                            <span class="text-muted">0.00</span>
                        @endif
                    </td>
                    <td>
                        @if ((float) $entry->manual_deduction > 0)
                            <span class="text-danger fw-semibold">-{{ \App\Support\Money::format($entry->manual_deduction) }}</span>
                            <div class="small text-muted">{{ $entry->manual_deduction_reason ?: 'Manual' }}</div>
                        @else
                            <span class="text-muted">0.00</span>
                        @endif
                    </td>
                    <td class="text-danger">-{{ \App\Support\Money::format($entry->employee_epf) }}</td>
                    <td>
                        @if ((float) $entry->apit_deduction > 0)
                            <span class="text-danger">-{{ \App\Support\Money::format($entry->apit_deduction) }}</span>
                        @else
                            <span class="text-muted">0.00</span>
                        @endif
                    </td>
                    <td>
                        <strong class="text-success fs-6">{{ \App\Support\Money::format($entry->net_pay) }}</strong>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.hr.payroll.entries.payslip', $entry) }}" class="btn btn-outline-info" title="View Official Payslip">
                                <i class="bi bi-file-earmark-text me-1"></i>Payslip
                            </a>
                            @if($payrollRun->status === 'draft')
                                <button type="button" class="btn btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $entry->id }}" title="Configure Deductions & Earnings">
                                    <i class="bi bi-pencil-square me-1"></i>Deductions & Pay
                                </button>
                            @endif
                        </div>

                        @if($payrollRun->status === 'draft')
                        <!-- Edit Entry & Salary Deductions Modal -->
                        <div class="modal fade text-start" id="editEntryModal{{ $entry->id }}" tabindex="-1" aria-labelledby="editEntryModalLabel{{ $entry->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.hr.payroll.entries.update', $entry) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" id="baseSalaryVal_{{ $entry->id }}" value="{{ (float) $entry->base_salary }}">
                                        <input type="hidden" id="leaveDeductionVal_{{ $entry->id }}" value="{{ (float) $entry->leave_deduction }}">
                                        <input type="hidden" id="epfVal_{{ $entry->id }}" value="{{ (float) $entry->employee_epf }}">

                                        <div class="modal-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                    <i class="bi bi-calculator-fill fs-5"></i>
                                                </div>
                                                <div>
                                                    <h5 class="modal-title mb-0" id="editEntryModalLabel{{ $entry->id }}">
                                                        Salary Deductions & Pay &mdash; {{ $entry->employee->employee_no }}
                                                    </h5>
                                                    <small class="text-muted">
                                                        {{ $entry->employee->user?->name ?? $entry->employee->job_title }} &bull; {{ $entry->employee->branch?->name ?? 'Main Campus' }}
                                                    </small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="alert alert-info py-2 px-3 small mb-4 d-flex align-items-center justify-content-between">
                                                <div>
                                                    <i class="bi bi-person-badge me-1"></i>
                                                    Configure earnings, automatic leave deductions, and custom manual deductions for <strong>{{ $entry->employee->user?->name ?? $entry->employee->job_title }}</strong>.
                                                </div>
                                                <span class="badge bg-secondary-subtle text-body border">{{ $entry->employee->branch?->name ?? 'Campus' }}</span>
                                            </div>

                                            <div class="row g-4">
                                                <!-- Left Column: 1. Monthly Earnings -->
                                                <div class="col-md-6">
                                                    <div class="card h-100 border bg-body-tertiary">
                                                        <div class="card-header bg-transparent py-2 fw-semibold text-success d-flex align-items-center justify-content-between">
                                                            <span><i class="bi bi-cash-stack me-1"></i>1. Monthly Earnings</span>
                                                            <span class="badge bg-success-subtle text-success border">Additions</span>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold text-muted mb-1">Base Salary (Fixed Monthly)</label>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-body-secondary fw-semibold">LKR</span>
                                                                    <input class="form-control fw-bold" type="text" value="{{ number_format($entry->base_salary, 2) }}" readonly>
                                                                </div>
                                                                <div class="form-text small text-muted">Fixed in employee appointment contract.</div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold mb-1" for="allowance_{{ $entry->id }}">Monthly Allowance</label>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-body-secondary fw-semibold">LKR</span>
                                                                    <input class="form-control allowance-input fw-semibold" id="allowance_{{ $entry->id }}" type="number" min="0" step="0.01" name="allowance" value="{{ $entry->allowance }}" required data-entry-id="{{ $entry->id }}">
                                                                </div>
                                                                <div class="form-text small text-muted">Performance, transport, or special allowances.</div>
                                                            </div>

                                                            <div class="p-2 rounded bg-success-subtle border border-success-subtle mt-4">
                                                                <div class="d-flex justify-content-between align-items-center small">
                                                                    <span class="text-success fw-semibold">Total Gross Earnings:</span>
                                                                    <strong class="text-success fs-6" id="grossPreview_{{ $entry->id }}">
                                                                        LKR {{ number_format($entry->base_salary + $entry->allowance, 2) }}
                                                                    </strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right Column: 2. Deductions & Statutory Taxes -->
                                                <div class="col-md-6">
                                                    <div class="card h-100 border bg-body-tertiary">
                                                        <div class="card-header bg-transparent py-2 fw-semibold text-danger d-flex align-items-center justify-content-between">
                                                            <span><i class="bi bi-dash-circle me-1"></i>2. Deductions & Taxes</span>
                                                            <span class="badge bg-danger-subtle text-danger border">Subtractions</span>
                                                        </div>
                                                        <div class="card-body">
                                                            <!-- Automatic Attendance / Leave Deduction -->
                                                            <div class="mb-3 p-2 rounded border bg-body-secondary">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <label class="form-label small fw-bold text-danger mb-0">
                                                                        <i class="bi bi-calendar-x me-1"></i>Automatic Leave Deduction
                                                                    </label>
                                                                    <span class="badge bg-danger-subtle text-danger border">
                                                                        {{ $entry->unpaid_leave_days }} Day{{ $entry->unpaid_leave_days == 1 ? '' : 's' }}
                                                                    </span>
                                                                </div>
                                                                <div class="input-group input-group-sm mb-1">
                                                                    <span class="input-group-text bg-body-tertiary text-danger fw-semibold">- LKR</span>
                                                                    <input class="form-control text-danger fw-bold" type="text" value="{{ number_format($entry->leave_deduction, 2) }}" readonly>
                                                                </div>
                                                                <div class="form-text small text-muted" style="font-size: 0.74rem;">
                                                                    @if ($autoMode === 'fixed_amount')
                                                                        Calculated via Super Admin fixed rate: <strong>Rs. {{ number_format($fixedAmount, 2) }}</strong> &times; {{ $entry->unpaid_leave_days }} days.
                                                                    @else
                                                                        Calculated via pro-rata rate: <strong>(Base / 30)</strong> &times; {{ $entry->unpaid_leave_days }} days.
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <!-- Super Admin Manual Deduction -->
                                                            @if (auth()->user()?->isSuperAdmin())
                                                                <div class="mb-3 p-2 rounded border bg-body-secondary">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <label class="form-label small fw-bold text-danger mb-0">
                                                                            <i class="bi bi-shield-lock-fill me-1"></i>Super Admin Salary Deduction
                                                                        </label>
                                                                        <a href="{{ route('admin.salary-deductions.index', ['employee_id' => $entry->employee_id]) }}" class="small text-decoration-none" target="_blank" style="font-size: 0.75rem;">
                                                                            Console <i class="bi bi-box-arrow-up-right"></i>
                                                                        </a>
                                                                    </div>
                                                                    <div class="input-group input-group-sm mb-2">
                                                                        <span class="input-group-text bg-body-tertiary text-danger fw-semibold">- LKR</span>
                                                                        <input class="form-control manual-deduction-input text-danger fw-semibold" id="manualDeduction_{{ $entry->id }}" type="number" min="0" step="0.01" name="manual_deduction" value="{{ $entry->manual_deduction }}" placeholder="0.00" data-entry-id="{{ $entry->id }}">
                                                                    </div>
                                                                    <label class="form-label small text-muted mb-1" style="font-size: 0.75rem;">Deduction Reason</label>
                                                                    <input class="form-control form-control-sm mb-1" type="text" id="manualReason_{{ $entry->id }}" name="manual_deduction_reason" value="{{ $entry->manual_deduction_reason }}" placeholder="e.g. Salary Advance, Damage, Loan">
                                                                    <div class="d-flex gap-1 flex-wrap mt-1">
                                                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('manualReason_{{ $entry->id }}').value='Salary Advance'">Salary Advance</button>
                                                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('manualReason_{{ $entry->id }}').value='Loan Repayment'">Loan</button>
                                                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('manualReason_{{ $entry->id }}').value='Property Damage'">Damage</button>
                                                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('manualReason_{{ $entry->id }}').value='Disciplinary Penalty'">Penalty</button>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="mb-3 p-2 rounded border bg-body-secondary">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <label class="form-label small fw-bold text-muted mb-0">
                                                                            <i class="bi bi-shield-lock me-1"></i>Salary Deductions (Super Admin Restrict)
                                                                        </label>
                                                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">Governed</span>
                                                                    </div>
                                                                    @if ((float) $entry->manual_deduction > 0)
                                                                        <div class="text-danger fw-bold small">- LKR {{ number_format($entry->manual_deduction, 2) }}</div>
                                                                        <div class="small text-muted fst-italic">{{ $entry->manual_deduction_reason ?: 'Super Admin Adjustment' }}</div>
                                                                    @else
                                                                        <div class="small text-muted">No manual deductions applied.</div>
                                                                    @endif
                                                                    <div class="form-text small text-muted" style="font-size: 0.72rem;">Custom salary deductions are managed exclusively by Super Admin.</div>
                                                                </div>
                                                            @endif

                                                            <!-- Statutory EPF & APIT -->
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">EPF (8% Employee)</label>
                                                                    <input class="form-control form-control-sm text-danger" type="text" value="- LKR {{ number_format($entry->employee_epf, 2) }}" readonly>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label small fw-semibold mb-1" for="apit_{{ $entry->id }}">APIT Tax</label>
                                                                    <div class="input-group input-group-sm">
                                                                        <input class="form-control apit-input text-danger fw-semibold" id="apit_{{ $entry->id }}" type="number" min="0" step="0.01" name="apit_deduction" value="{{ $entry->apit_deduction }}" required data-entry-id="{{ $entry->id }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Live Interactive Net Pay Summary Banner -->
                                            <div class="card border border-primary-subtle bg-primary-subtle mt-4">
                                                <div class="card-body py-2 px-3">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                        <div>
                                                            <span class="text-primary fw-bold fs-6">
                                                                <i class="bi bi-wallet2 me-1"></i>Estimated Net Pay Preview:
                                                            </span>
                                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                                Gross Earnings &minus; (EPF + Leave Deductions + Manual Deductions + APIT Tax)
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="fs-4 fw-bolder text-primary" id="netPreview_{{ $entry->id }}">
                                                                LKR {{ number_format($entry->net_pay, 2) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Notes (Optional) -->
                                            <div class="mt-3">
                                                <label class="form-label small text-muted mb-1">Notes (optional)</label>
                                                <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Administrative notes for this payroll entry...">{{ $entry->notes }}</textarea>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-check-lg me-1"></i> Save & Recalculate Net Pay
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No payroll entries found for this run.</td></tr>
            @endforelse
            </tbody>
            @php($totalNet = $payrollRun->entries->reduce(fn ($total, $entry) => \App\Support\Money::add($total, $entry->net_pay), '0.00'))
            <tfoot class="table-light">
                <tr>
                    <th colspan="7" class="text-end">Total Net Payable:</th>
                    <th><strong class="text-success fs-5">{{ \App\Support\Money::format($totalNet) }}</strong></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-3">
    @if($payrollRun->status === 'draft')
        @can('payroll.approve')
            <form method="POST" action="{{ route('admin.hr.payroll.approve', $payrollRun) }}">
                @csrf
                <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Approve & Lock Payroll</button>
            </form>
        @endcan
    @elseif($payrollRun->status === 'approved')
        @can('payroll.pay')
            <form method="POST" action="{{ route('admin.hr.payroll.paid', $payrollRun) }}" class="d-flex gap-2">
                @csrf
                <select name="payout_account_id" class="form-select" required aria-label="Payroll payout account">
                    @foreach($payoutAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} &bull; {{ $account->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit"><i class="bi bi-cash-stack me-1"></i>Mark Paid & Post Journal</button>
            </form>
        @endcan
    @endif
</div>
<p class="small text-muted mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Automatic leave-based salary deductions are computed from approved unpaid leaves and attendance records (configurable by Super Admin). Manual deductions are tracked with itemized audit reasons. Statutory Sri Lankan EPF (8% employee, 12% employer), ETF (3% employer), and APIT brackets are applied automatically.
</p>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.allowance-input, .apit-input, .manual-deduction-input').forEach(input => {
            input.addEventListener('input', function() {
                const entryId = this.dataset.entryId;
                if (!entryId) return;

                const baseSalary = parseFloat(document.getElementById('baseSalaryVal_' + entryId)?.value || '0');
                const allowance = parseFloat(document.getElementById('allowance_' + entryId)?.value || '0');
                const leaveDeduction = parseFloat(document.getElementById('leaveDeductionVal_' + entryId)?.value || '0');
                const epf = parseFloat(document.getElementById('epfVal_' + entryId)?.value || '0');
                const apit = parseFloat(document.getElementById('apit_' + entryId)?.value || '0');
                const manual = parseFloat(document.getElementById('manualDeduction_' + entryId)?.value || '0');

                const gross = Math.max(0, baseSalary + allowance);
                const totalDeductions = epf + leaveDeduction + apit + manual;
                const net = Math.max(0, gross - totalDeductions);

                const grossPreview = document.getElementById('grossPreview_' + entryId);
                if (grossPreview) {
                    grossPreview.textContent = 'LKR ' + gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                const netPreview = document.getElementById('netPreview_' + entryId);
                if (netPreview) {
                    netPreview.textContent = 'LKR ' + net.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            });
        });
    });
</script>
@endsection
