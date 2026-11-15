@extends('Layouts.LayoutApp')
@section('title', 'Payslip - ' . ($payrollEntry->employee?->user?->name ?? $payrollEntry->employee?->employee_no))
@section('content')

<style>
/* ==========================================================================
   OFFICIAL PAYSLIP DOCUMENT STYLING (Night-Mode Immune + Print Optimized)
   ========================================================================== */
.payslip-wrapper {
    display: flex;
    justify-content: center;
    padding: 1rem 0 3rem;
}

.payslip-sheet {
    width: 100%;
    max-width: 860px;
    background-color: #ffffff !important;
    color: #0f172a !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    position: relative;
}

/* Force dark text and high-contrast styling inside the sheet even in dark mode */
[data-bs-theme="dark"] .payslip-sheet,
[data-bs-theme="light"] .payslip-sheet {
    background-color: #ffffff !important;
    color: #0f172a !important;
}

.payslip-sheet h1, .payslip-sheet h2, .payslip-sheet h3, 
.payslip-sheet h4, .payslip-sheet h5, .payslip-sheet h6,
.payslip-sheet strong, .payslip-sheet b, .payslip-sheet span,
.payslip-sheet td, .payslip-sheet th, .payslip-sheet div {
    color: #0f172a;
}

.payslip-sheet .text-muted {
    color: #64748b !important;
}

.payslip-sheet .text-primary {
    color: #2563eb !important;
}

.payslip-sheet .text-success {
    color: #059669 !important;
}

.payslip-sheet .text-danger {
    color: #dc2626 !important;
}

.payslip-sheet .table {
    --bs-table-bg: transparent !important;
    --bs-table-color: #0f172a !important;
    border-color: #e2e8f0 !important;
    margin-bottom: 0;
}

.payslip-sheet .table td, 
.payslip-sheet .table th {
    padding: 0.55rem 0.75rem;
    color: #0f172a !important;
    background: transparent !important;
    border-color: #f1f5f9 !important;
}

.payslip-sheet .payslip-box {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px;
}

.payslip-sheet .payslip-box * {
    color: #1e293b;
}

.payslip-sheet .payslip-header-bar {
    background-color: #f1f5f9 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    font-weight: 700;
    color: #0f172a !important;
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
}

.payslip-sheet .payslip-net-box {
    background-color: #f0fdf4 !important;
    border: 2px solid #10b981 !important;
    border-radius: 10px;
    padding: 1.25rem 1.5rem;
}

.payslip-sheet .payslip-net-box * {
    color: #065f46 !important;
}

.payslip-sheet .payslip-net-amount {
    color: #047857 !important;
    font-size: 2rem;
    font-weight: 800;
}

/* ==========================================================================
   PRINT STYLES FOR CRISP HIGH-RES A4 PORTRAIT PDF / PRINTING
   ========================================================================== */
@media print {
    @page {
        size: A4 portrait !important;
        margin: 10mm 12mm !important;
    }
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .no-print, .app-sidebar, .sidebar, .topbar, nav, header, footer, .btn, .app-footer, .brand {
        display: none !important;
    }
    .app-shell, .app-main, .content, .container-fluid, .payslip-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
    }
    .payslip-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    @if (auth()->user()?->isTeacher())
        <a href="{{ route('teacher.profile') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to My Profile
        </a>
    @else
        <a href="{{ route('admin.hr.payroll.show', $payrollEntry->payroll_run_id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Payroll Run
        </a>
    @endif
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print / Download PDF
    </button>
</div>

<div class="payslip-wrapper">
    <div class="payslip-sheet">
        <!-- School Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
                    <i class="bi bi-mortarboard-fill fs-2"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold" style="color: #0f172a !important;">{{ config('school.institution_name', 'IGK International School') }}</h4>
                    <div class="text-muted small">{{ config('school.institution_address', 'Colombo, Sri Lanka') }} &bull; {{ config('school.institution_phone', '+94 11 234 5600') }}</div>
                    <div class="text-muted small">Campus: <strong style="color: #0f172a !important;">{{ $payrollEntry->employee?->branch?->name ?? 'Main Campus' }}</strong></div>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-secondary fs-6 text-uppercase px-3 py-2">PAYSLIP</span>
                <div class="text-muted small mt-2">Ref: #PAY-{{ $payrollEntry->id }}</div>
                <div class="small fw-semibold mt-1">
                    Status: 
                    @php
                        $runStatus = strtolower($payrollEntry->run?->status ?? 'draft');
                        $statusBadge = match ($runStatus) {
                            'paid' => 'bg-success',
                            'approved' => 'bg-info text-dark',
                            default => 'bg-warning text-dark',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ strtoupper($runStatus) }}</span>
                </div>
            </div>
        </div>

        <!-- Period & Employee Meta -->
        <div class="row g-3 mb-4 p-3 rounded payslip-box">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><td class="text-muted fw-semibold" style="width: 40%;">Employee Name:</td><td><strong style="color: #0f172a !important;">{{ $payrollEntry->employee?->user?->name ?? $payrollEntry->employee?->teacher?->user?->name ?? '—' }}</strong></td></tr>
                        <tr><td class="text-muted fw-semibold">Employee No:</td><td><code>{{ $payrollEntry->employee?->employee_no }}</code></td></tr>
                        <tr><td class="text-muted fw-semibold">Designation:</td><td>{{ $payrollEntry->employee?->job_title }}</td></tr>
                        <tr><td class="text-muted fw-semibold">Department:</td><td>{{ $payrollEntry->employee?->department?->name ?? 'General Staff' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><td class="text-muted fw-semibold" style="width: 40%;">Pay Period:</td><td>{{ $payrollEntry->run?->period_start?->format('d M Y') ?? '—' }} — {{ $payrollEntry->run?->period_end?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><td class="text-muted fw-semibold">Month:</td><td><strong style="color: #0f172a !important;">{{ $payrollEntry->run?->period_start?->format('F Y') ?? '—' }}</strong></td></tr>
                        <tr><td class="text-muted fw-semibold">APIT Tax Mode:</td><td>{{ ucfirst($payrollEntry->employee?->apit_mode ?? 'Primary') }}</td></tr>
                        <tr><td class="text-muted fw-semibold">Paid Date:</td><td>{{ $payrollEntry->run?->paid_at ? $payrollEntry->run->paid_at->format('d M Y') : 'Pending Payout' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Earnings & Deductions Tables -->
        <div class="row g-4 mb-4">
            <!-- Earnings -->
            <div class="col-md-6">
                <div class="border rounded h-100 overflow-hidden" style="border-color: #e2e8f0 !important;">
                    <div class="payslip-header-bar text-uppercase"><i class="bi bi-plus-circle text-success me-1"></i> Gross Earnings</div>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="py-2 px-3">Basic Monthly Salary</td>
                                <td class="text-end py-2 px-3 fw-semibold">Rs. {{ number_format((float) $payrollEntry->base_salary, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3">Allowances</td>
                                <td class="text-end py-2 px-3">Rs. {{ number_format((float) $payrollEntry->allowance, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top" style="border-color: #e2e8f0 !important;">
                            <tr class="fw-bold" style="background-color: #f8fafc !important;">
                                <td class="py-2 px-3">Total Earnings</td>
                                <td class="text-end py-2 px-3" style="color: #0f172a !important;">Rs. {{ number_format((float) ($payrollEntry->base_salary + $payrollEntry->allowance), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Deductions -->
            <div class="col-md-6">
                <div class="border rounded h-100 overflow-hidden" style="border-color: #e2e8f0 !important;">
                    <div class="payslip-header-bar text-uppercase"><i class="bi bi-dash-circle text-danger me-1"></i> Itemized Deductions</div>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <!-- Automatic Unpaid Leave / Attendance Deduction -->
                            <tr>
                                <td class="py-2 px-3">
                                    <div>Automatic Attendance Deduction</div>
                                    @if ($payrollEntry->unpaid_leave_days > 0)
                                        <small class="text-muted">({{ $payrollEntry->unpaid_leave_days }} unpaid day{{ $payrollEntry->unpaid_leave_days > 1 ? 's' : '' }} deduction)</small>
                                    @else
                                        <small class="text-muted">(0 unpaid days)</small>
                                    @endif
                                </td>
                                <td class="text-end py-2 px-3 text-danger fw-semibold">
                                    -Rs. {{ number_format((float) $payrollEntry->leave_deduction, 2) }}
                                </td>
                            </tr>

                            <!-- Manual Admin Deduction -->
                            @if ((float) $payrollEntry->manual_deduction > 0)
                            <tr>
                                <td class="py-2 px-3">
                                    <div>Manual Super Admin Deduction</div>
                                    <small class="text-muted">({{ $payrollEntry->manual_deduction_reason ?: 'Administrative adjustment' }})</small>
                                </td>
                                <td class="text-end py-2 px-3 text-danger fw-semibold">
                                    -Rs. {{ number_format((float) $payrollEntry->manual_deduction, 2) }}
                                </td>
                            </tr>
                            @endif

                            <!-- Statutory Employee EPF -->
                            <tr>
                                <td class="py-2 px-3">
                                    <div>Employee EPF (8%)</div>
                                    <small class="text-muted">Statutory contribution</small>
                                </td>
                                <td class="text-end py-2 px-3 text-danger fw-semibold">
                                    -Rs. {{ number_format((float) $payrollEntry->employee_epf, 2) }}
                                </td>
                            </tr>

                            <!-- APIT Tax -->
                            <tr>
                                <td class="py-2 px-3">
                                    <div>APIT Income Tax</div>
                                    <small class="text-muted">PAYE withholding</small>
                                </td>
                                <td class="text-end py-2 px-3 text-danger fw-semibold">
                                    -Rs. {{ number_format((float) $payrollEntry->apit_deduction, 2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top" style="border-color: #e2e8f0 !important;">
                            @php
                                $totalAllDeductions = (float) $payrollEntry->leave_deduction + (float) $payrollEntry->manual_deduction + (float) $payrollEntry->employee_epf + (float) $payrollEntry->apit_deduction;
                            @endphp
                            <tr class="fw-bold" style="background-color: #f8fafc !important;">
                                <td class="py-2 px-3">Total Deductions</td>
                                <td class="text-end py-2 px-3 text-danger fw-bold">-Rs. {{ number_format($totalAllDeductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net Salary Highlight -->
        <div class="payslip-net-box mb-4 d-flex justify-content-between align-items-center">
            <div>
                <div class="text-uppercase small fw-bold mb-1" style="color: #065f46 !important;">Net Take-Home Pay (Payable)</div>
                <div class="small" style="color: #047857 !important;">Direct credit to employee registered bank account</div>
            </div>
            <div class="text-end">
                <span class="payslip-net-amount">{{ config('school.default_currency', 'LKR') }} {{ number_format((float) $payrollEntry->net_pay, 2) }}</span>
            </div>
        </div>

        <!-- Employer Statutory Contributions (Informational) -->
        <div class="border rounded p-3 mb-4 payslip-box">
            <div class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-shield-check me-1 text-primary"></i> Employer Statutory Contributions (School Paid &bull; Non-Deductible)</div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="small text-muted">Employer EPF (12%)</div>
                    <div class="fw-bold" style="color: #0f172a !important;">Rs. {{ number_format((float) $payrollEntry->employer_epf, 2) }}</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Employer ETF (3%)</div>
                    <div class="fw-bold" style="color: #0f172a !important;">Rs. {{ number_format((float) $payrollEntry->employer_etf, 2) }}</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Total Contribution</div>
                    <div class="fw-bold" style="color: #0f172a !important;">Rs. {{ number_format((float) ($payrollEntry->employer_epf + $payrollEntry->employer_etf), 2) }}</div>
                </div>
            </div>
        </div>

        @if ($payrollEntry->notes)
            <div class="small text-muted mb-4 p-2 rounded payslip-box">
                <strong style="color: #0f172a !important;">Administrative Notes:</strong> {{ $payrollEntry->notes }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="row g-4 pt-4 mt-2 border-top text-center" style="border-color: #e2e8f0 !important;">
            <div class="col-6">
                <div style="border-top: 1px dashed #94a3b8; width: 60%; margin: 35px auto 5px;"></div>
                <small class="text-muted fw-semibold">Employee Signature</small>
            </div>
            <div class="col-6">
                <div style="border-top: 1px dashed #94a3b8; width: 60%; margin: 35px auto 5px;"></div>
                <small class="text-muted fw-semibold">Authorized Signatory / Principal</small>
            </div>
        </div>
    </div>
</div>
@endsection
