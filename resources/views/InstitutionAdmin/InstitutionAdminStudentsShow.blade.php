@extends('Layouts.LayoutApp')
@section('title', 'Student Profile - ' . $student->user->name)
@section('content')
<div class="page-head mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-lg bg-primary text-white fs-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
            {{ strtoupper(substr($student->user->name, 0, 1)) }}
        </div>
        <div>
            <h3 class="mb-0 d-flex align-items-center gap-2">
                {{ $student->user->name }}
                <span class="badge {{ $student->status === 'active' ? 'bg-success' : 'bg-secondary' }} fs-6">{{ ucfirst($student->status) }}</span>
            </h3>
            <div class="text-muted small d-flex flex-wrap align-items-center gap-2 mt-1">
                <span><i class="bi bi-person-badge me-1"></i>Admission No: <strong class="text-primary">{{ $student->admission_no }}</strong></span>
                <span>&bull;</span>
                <span><i class="bi bi-building me-1"></i>{{ $student->branch?->name ?? 'Main Campus' }}</span>
                <span>&bull;</span>
                <span><i class="bi bi-mortarboard me-1"></i>{{ $student->class?->full_name ?? 'Unassigned' }}</span>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
        @can('update', $student)
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Campus
        </button>
        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i> Edit Profile
        </a>
        @endcan
        <a href="{{ route('admin.students.report-card', $student) }}" class="btn btn-outline-info">
            <i class="bi bi-award me-1"></i> Report Card
        </a>
    </div>
</div>

@include('Layouts.LayoutErrors')

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Attendance Rate</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-success">{{ $attendanceSummary['percentage'] }}%</span>
                    <span class="text-muted small">({{ $attendanceSummary['present'] }}/{{ $attendanceSummary['total'] }} days)</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Subjects Enrolled</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-primary">{{ $student->classSubjects->where('pivot.status', 'enrolled')->count() }}</span>
                    <span class="text-muted small">Active courses</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Fee Outstanding</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold {{ $invoicesSummary['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($invoicesSummary['balance'], 2) }}
                    </span>
                    <span class="text-muted small">{{ config('school.default_currency', 'LKR') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Academic Program</div>
                <div class="fs-5 fw-bold text-truncate" title="{{ $student->program?->name ?? 'Standard Program' }}">
                    {{ $student->program?->name ?? 'Standard' }}
                </div>
                <span class="text-muted small">{{ $student->academicYear?->name ?? 'Academic Year' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 w-100" id="studentProfileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
            <i class="bi bi-person-lines-fill me-1"></i> Personal & Guardian
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="subjects-tab" data-bs-toggle="tab" data-bs-target="#subjects" type="button" role="tab">
            <i class="bi bi-book me-1"></i> Subject Enrollments
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
            <i class="bi bi-calendar-check me-1"></i> Attendance
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="finances-tab" data-bs-toggle="tab" data-bs-target="#finances" type="button" role="tab">
            <i class="bi bi-receipt me-1"></i> Fee Invoices
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers" type="button" role="tab">
            <i class="bi bi-arrow-left-right me-1"></i> Branch Transfers
            @if ($student->branchTransfers->isNotEmpty())
                <span class="badge bg-secondary ms-1">{{ $student->branchTransfers->count() }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
            <i class="bi bi-file-earmark-text me-1"></i> Documents
        </button>
    </li>
</ul>

<div class="tab-content" id="studentProfileTabsContent">
    <!-- Tab 1: Personal & Guardian -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-person me-2"></i>Personal Details</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr><th class="text-muted" style="width: 38%;">Full Name:</th><td>{{ $student->user->name }}</td></tr>
                                <tr><th class="text-muted">Login Email:</th><td>{{ $student->user->email }}</td></tr>
                                <tr><th class="text-muted">Admission Number:</th><td><span class="badge bg-primary-subtle text-primary border">{{ $student->admission_no }}</span></td></tr>
                                <tr><th class="text-muted">Admission Date:</th><td>{{ $student->admission_date ? $student->admission_date->format('d M Y') : '—' }}</td></tr>
                                <tr><th class="text-muted">Date of Birth:</th><td>{{ $student->dob ? $student->dob->format('d M Y') : '—' }}</td></tr>
                                <tr><th class="text-muted">Gender:</th><td>{{ ucfirst($student->gender ?? '—') }}</td></tr>
                                <tr><th class="text-muted">Nationality:</th><td>{{ $student->nationality ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Identification:</th><td>{{ strtoupper(str_replace('_', ' ', $student->document_type ?? 'None')) }}: <code>{{ $student->document_number ?: '—' }}</code></td></tr>
                                <tr><th class="text-muted">Issuing Country:</th><td>{{ $student->issuing_country ?? '—' }}</td></tr>
                                <tr><th class="text-muted">Phone:</th><td>{{ $student->phone ?: '—' }}</td></tr>
                                <tr><th class="text-muted">Campus / Branch:</th><td><strong>{{ $student->branch?->name ?? 'Main Campus' }}</strong> ({{ $student->branch?->code ?? 'MAIN' }})</td></tr>
                                <tr><th class="text-muted">Current Class:</th><td>{{ $student->class?->full_name ?? 'Unassigned' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-people me-2"></i>Guardian & Family Details</div>
                    <div class="card-body">
                        @php $primary = $student->primaryGuardian->first(); @endphp
                        @if ($primary)
                            <div class="p-3 bg-body-tertiary rounded mb-3">
                                <div class="badge bg-success mb-2">Primary Guardian</div>
                                <h5 class="mb-1">{{ $primary->user?->name ?? 'Guardian' }}</h5>
                                <div class="small text-muted mb-2">Relationship: {{ ucfirst($primary->pivot->relationship ?? 'Guardian') }}</div>
                                <div class="small"><i class="bi bi-envelope me-1"></i>{{ $primary->user?->email ?? '—' }}</div>
                                <div class="small"><i class="bi bi-telephone me-1"></i>{{ $primary->phone ?: '—' }}</div>
                                <div class="small"><i class="bi bi-geo-alt me-1"></i>{{ $primary->address ?: '—' }}</div>
                            </div>
                        @else
                            <div class="text-muted py-3">No primary guardian linked.</div>
                        @endif

                        @if ($student->guardians->where('id', '!=', $primary?->id)->isNotEmpty())
                            <h6 class="text-muted small text-uppercase mt-3 mb-2">Secondary Guardians</h6>
                            <div class="list-group list-group-flush">
                                @foreach ($student->guardians->where('id', '!=', $primary?->id) as $g)
                                    <div class="list-group-item px-0 py-2">
                                        <div class="fw-semibold">{{ $g->user?->name }}</div>
                                        <div class="small text-muted">{{ $g->phone }} &bull; {{ $g->user?->email }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Subject Enrollments -->
    <div class="tab-pane fade" id="subjects" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-journal-check me-2"></i>Enrolled Subjects</span>
                <span class="badge bg-primary">{{ $student->classSubjects->count() }} Total Courses</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th>Code</th>
                            <th>Term</th>
                            <th>Class Offering</th>
                            <th>Assigned Teacher</th>
                            <th>Status</th>
                            <th>Enrolled Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->classSubjects as $cs)
                            <tr>
                                <td><strong>{{ $cs->subject?->name }}</strong></td>
                                <td><code>{{ $cs->subject?->code }}</code></td>
                                <td>{{ $cs->term?->name ?? 'Current Term' }}</td>
                                <td>{{ $cs->schoolClass?->full_name }}</td>
                                <td>{{ $cs->teacher?->user?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $cs->pivot->status === 'enrolled' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($cs->pivot->status) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $cs->pivot->enrolled_at ? \Carbon\Carbon::parse($cs->pivot->enrolled_at)->format('d M Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No subject enrollments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Attendance -->
    <div class="tab-pane fade" id="attendance" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-calendar2-check me-2"></i>Attendance Log</span>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success border">Present: {{ $attendanceSummary['present'] }}</span>
                    <span class="badge bg-warning-subtle text-warning border">Late: {{ $attendanceSummary['late'] }}</span>
                    <span class="badge bg-danger-subtle text-danger border">Absent: {{ $attendanceSummary['absent'] }}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Remarks / Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->attendance as $att)
                            <tr>
                                <td>{{ $att->date ? \Carbon\Carbon::parse($att->date)->format('D, d M Y') : '—' }}</td>
                                <td>
                                    @php
                                        $attBadge = match($att->status) {
                                            'present' => 'bg-success',
                                            'late' => 'bg-warning text-dark',
                                            'absent' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $attBadge }}">{{ ucfirst($att->status) }}</span>
                                </td>
                                <td class="small text-muted">{{ $att->remarks ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No attendance records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Invoices & Fees -->
    <div class="tab-pane fade" id="finances" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-cash-stack me-2"></i>Student Fee Invoices</span>
                @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.payments.create') }}?student_id={{ $student->id }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-credit-card me-1"></i> Record Payment
                </a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->invoices as $inv)
                            <tr>
                                <td><strong>{{ $inv->invoice_no }}</strong></td>
                                <td>{{ $inv->issue_date ? \Carbon\Carbon::parse($inv->issue_date)->format('d M Y') : '—' }}</td>
                                <td>{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : '—' }}</td>
                                <td>{{ number_format((float) $inv->total, 2) }}</td>
                                <td class="text-success">{{ number_format((float) $inv->amount_paid, 2) }}</td>
                                <td class="fw-semibold {{ (float) $inv->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $inv->balance, 2) }}
                                </td>
                                <td>
                                    @php
                                        $invBadge = match($inv->status) {
                                            'paid' => 'bg-success',
                                            'partial' => 'bg-warning text-dark',
                                            'unpaid', 'overdue' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $invBadge }}">{{ ucfirst($inv->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No invoices issued for this student.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Branch Transfers -->
    <div class="tab-pane fade" id="transfers" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-arrow-left-right me-2"></i>Campus Transfer History</span>
                @can('update', $student)
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#transferModal">
                    <i class="bi bi-plus-lg me-1"></i> New Transfer
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Transfer Date</th>
                            <th>Previous Campus</th>
                            <th>Destination Campus</th>
                            <th>Old Admission No</th>
                            <th>New Admission No</th>
                            <th>Reason</th>
                            <th>Authorised Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->branchTransfers as $tr)
                            <tr>
                                <td>{{ $tr->transfer_date ? $tr->transfer_date->format('d M Y') : '—' }}</td>
                                <td><span class="badge bg-secondary-subtle text-body border">{{ $tr->fromBranch?->name ?? 'Original Branch' }}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary border">{{ $tr->toBranch?->name ?? 'Destination' }}</span></td>
                                <td><code>{{ $tr->previous_admission_no ?: '—' }}</code></td>
                                <td><strong class="text-success">{{ $tr->new_admission_no ?: '—' }}</strong></td>
                                <td class="small">{{ $tr->reason ?: '—' }}</td>
                                <td class="small text-muted">{{ $tr->transferredByUser?->name ?? 'System Admin' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No campus transfers recorded. Student remains in initial branch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 6: Documents -->
    <div class="tab-pane fade" id="documents" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-paperclip me-2"></i>Uploaded Documents</span>
                @can('create', App\Models\StudentDocument::class)
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#uploadDocCollapse">
                    <i class="bi bi-upload me-1"></i> Upload Document
                </button>
                @endcan
            </div>

            <div class="collapse p-3 bg-body-tertiary border-bottom" id="uploadDocCollapse">
                <form method="POST" action="{{ route('admin.students.documents.upload', $student) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="type" value="other">
                    <div class="col-md-9">
                        <label class="form-label small">Select Document File (PDF, PNG, JPG - max 10MB) <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.jpeg" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-cloud-arrow-up me-1"></i>Upload Document</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Document Type</th>
                            <th>Original Filename</th>
                            <th>Size</th>
                            <th>Uploaded Date</th>
                            <th class="text-end" style="min-width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->documents as $doc)
                            <tr>
                                <td><span class="badge bg-info-subtle text-info border">{{ ucwords(str_replace('_', ' ', $doc->type)) }}</span></td>
                                <td>{{ $doc->original_name }}</td>
                                <td class="small text-muted">{{ number_format($doc->size / 1024, 1) }} KB</td>
                                <td class="small text-muted">{{ $doc->created_at->format('d M Y, H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.students.documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                    @can('delete', $doc)
                                    <form method="POST" action="{{ route('admin.students.documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No verified documents uploaded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Branch Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.students.transfer', $student) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel"><i class="bi bi-arrow-left-right me-2"></i>Transfer Student Campus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        A destination branch sequence admission number will be generated automatically. Academic, attendance, and financial history will remain permanently preserved.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Campus</label>
                        <input type="text" class="form-control" value="{{ $student->branch?->name ?? 'Main Campus' }} ({{ $student->branch?->code ?? 'MAIN' }})" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Admission Number</label>
                        <input type="text" class="form-control" value="{{ $student->admission_no }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destination Campus <span class="text-danger">*</span></label>
                        <select name="to_branch_id" class="form-select" id="destinationBranchSelect" required>
                            <option value="">Select destination campus</option>
                            @foreach ($availableBranches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }} ({{ $br->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destination Class (Optional)</label>
                        <select name="class_id" class="form-select" id="destinationClassSelect">
                            <option value="">Keep current / Assign later</option>
                            @foreach ($branchClasses as $bc)
                                <option value="{{ $bc->id }}" data-branch="{{ $bc->branch_id }}">{{ $bc->full_name }} ({{ $bc->branch?->name ?? 'Branch' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Transfer</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Relocation, Campus consolidation, Parent request"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i>Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
