@extends('Layouts.LayoutApp')
@section('title', 'Report Card - ' . $student->user->name)

@section('content')
<div class="no-print d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">Academic Report Card</h3>
        <small class="text-muted">Official student term progress report and grade sheet.</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label for="term_select" class="form-label mb-0 fw-semibold text-nowrap">Term:</label>
            <select name="term_id" id="term_select" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach ($terms as $t)
                    <option value="{{ $t->id }}" @selected($selectedTerm && $selectedTerm->id == $t->id)>
                        {{ $t->name }} {{ $t->is_current ? '(Current Active)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print / PDF
        </button>
        <a href="{{ $downloadUrl }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="report-card-container">
    <div class="card shadow-sm border-0 report-card-sheet p-4 p-md-5">
        <!-- Institution Header -->
        <div class="report-header text-center pb-4 mb-4 border-bottom">
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <span class="brand-mark fs-3 text-primary"><i class="bi bi-mortarboard-fill"></i></span>
                <h2 class="h2 mb-0 fw-bold tracking-tight">{{ $institution['name'] }}</h2>
            </div>
            <div class="text-uppercase tracking-widest small fw-semibold text-muted mb-1">Official Student Academic Report</div>
            <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-6">
                {{ $selectedTerm ? $selectedTerm->academicYear->name . ' &middot; ' . $selectedTerm->name : 'All Recorded Published Exams' }}
            </div>
        </div>

        <!-- Student Meta Information -->
        <div class="row g-3 mb-4 p-3 rounded bg-body-tertiary">
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Student Name</div>
                <div class="fw-bold fs-6">{{ $student->user->name }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Admission Number</div>
                <div class="fw-bold fs-6"><code>{{ $student->admission_no }}</code></div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Class & Section</div>
                <div class="fw-bold fs-6">{{ $student->class?->full_name ?? '—' }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small text-uppercase fw-semibold">Guardian</div>
                <div class="fw-bold fs-6">{{ $student->primaryGuardian->first()?->user?->name ?? '—' }}</div>
            </div>
        </div>

        <!-- Subject Marks and GPA Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th class="text-start">Subject</th>
                        <th style="width: 12%;">Credit Hours</th>
                        <th style="width: 14%;">Max Marks</th>
                        <th style="width: 14%;">Obtained Marks</th>
                        <th style="width: 12%;">Percentage</th>
                        <th style="width: 12%;">Grade</th>
                        <th style="width: 14%;">Grade Point</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCredits = 0;
                        $totalObtained = 0;
                        $totalMax = 0;
                    @endphp
                    @if ($termResult)
                        @forelse ($subjectResults as $subjectResult)
                            @php
                                $totalCredits += $subjectResult->credit_hours ?? 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $subjectResult->subject->name }}</div>
                                    <small class="text-muted"><code>{{ $subjectResult->subject->code }}</code> &middot; Weighted term result</small>
                                </td>
                                <td class="text-center">{{ $subjectResult->credit_hours ?? '—' }}</td>
                                <td class="text-center">—</td>
                                <td class="text-center">{{ $subjectResult->result_status === 'excused' ? 'Excused' : '—' }}</td>
                                <td class="text-center">{{ $subjectResult->percentage === null ? '—' : $subjectResult->percentage.'%' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($subjectResult->grade ?? 'F') }} px-2 py-1">
                                        {{ $subjectResult->result_status === 'graded' ? $subjectResult->grade : ucfirst($subjectResult->result_status) }}
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">{{ $subjectResult->grade_point === null ? '—' : number_format((float) $subjectResult->grade_point, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No subject results are available for this term.</td></tr>
                        @endforelse
                    @else
                        @forelse ($results as $result)
                            @php
                                $subject = $result->exam->subject;
                                $credits = $subject->credit_hours ?? 3;
                                $maxMarks = $result->exam->max_marks;
                                $totalCredits += $credits;
                                $totalMax += $maxMarks;

                                if ($result->is_absent) {
                                    $percentage = 0.0;
                                    $grade = \App\Support\SchoolHelper::gradeFor($percentage, $gradeScale);
                                    $gp = \App\Support\SchoolHelper::gradePointFor($percentage, $gradeScale);
                                } else {
                                    $totalObtained += (float) $result->marks;
                                    $percentage = \App\Support\SchoolHelper::resultPercentage($result->marks, $maxMarks);
                                    $grade = \App\Support\SchoolHelper::gradeFor($percentage, $gradeScale);
                                    $gp = \App\Support\SchoolHelper::gradePointFor($percentage, $gradeScale);
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $subject->name }}</div>
                                    <small class="text-muted"><code>{{ $subject->code }}</code> &middot; {{ $result->exam->name }}</small>
                                </td>
                                <td class="text-center">{{ $credits }}</td>
                                <td class="text-center">{{ $maxMarks }}</td>
                                <td class="text-center">
                                    @if ($result->is_absent)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $result->is_excused ? 'Excused' : 'Absent' }}</span>
                                    @else
                                        <span class="fw-bold">{{ \App\Support\SchoolHelper::formatMarks($result->marks) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $result->is_absent ? '—' : $percentage . '%' }}</td>
                                <td class="text-center"><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }} px-2 py-1">{{ $grade }}</span></td>
                                <td class="text-center fw-semibold">{{ number_format($gp, 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No published results available for this term.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Academic & Attendance Summary Grid -->
        <div class="row g-3 mb-5">
            <div class="col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent fw-semibold py-2">
                        <i class="bi bi-award-fill text-warning me-1"></i> Academic Summary
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total Credit Hours Enrolled:</span>
                            <span class="fw-bold">{{ $totalCredits }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">{{ $termResult ? 'Final Result:' : 'Aggregate Marks:' }}</span>
                            <span class="fw-bold">{{ $termResult ? ucfirst($termResult->result_status) : $totalObtained.' / '.$totalMax }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 align-items-center">
                            <span class="text-muted fw-semibold">Term Grade Point Average (GPA):</span>
                            <span class="fs-4 fw-bold text-primary">{{ $gpa !== null ? number_format($gpa, 2) : '—' }} <small class="fs-6 text-muted">/ {{ number_format((float) $gradePointMax, 2) }}</small></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent fw-semibold py-2">
                        <i class="bi bi-calendar2-check-fill text-success me-1"></i> Attendance Record
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">School Days Counted:</span>
                            <span class="fw-bold">{{ $attendance['total'] }} days</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Days Present / Late:</span>
                            <span class="fw-bold text-success">{{ $attendance['present'] }} present, {{ $attendance['late'] }} late</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 align-items-center">
                            <span class="text-muted fw-semibold">Overall Attendance Rate:</span>
                            <span class="fs-4 fw-bold {{ $attendance['percentage'] >= 75 ? 'text-success' : 'text-danger' }}">
                                {{ $attendance['percentage'] }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Scale Reference -->
        <div class="p-2 mb-5 rounded bg-body-tertiary small text-center text-muted">
            <span class="fw-semibold">Official Grading Scale:</span>
            @foreach ($gradeItems as $index => $gradeItem)
                {{ $gradeItem->grade }} ({{ number_format((float) $gradeItem->min_percent, 2) }}–{{ number_format((float) $gradeItem->max_percent, 2) }}% &bull; GP {{ number_format((float) $gradeItem->grade_point, 2) }}){{ $index < $gradeItems->count() - 1 ? ' | ' : '' }}
            @endforeach
        </div>

        <!-- Signatures Block -->
        <div class="row pt-4 text-center">
            <div class="col-4">
                <div class="signature-line border-bottom pb-4 mb-2"></div>
                <div class="fw-semibold small">Class Teacher</div>
                <small class="text-muted">Date & Signature</small>
            </div>
            <div class="col-4">
                <div class="signature-line border-bottom pb-4 mb-2"></div>
                <div class="fw-semibold small">Institution Administrator</div>
                <small class="text-muted">School Seal & Stamp</small>
            </div>
            <div class="col-4">
                <div class="signature-line border-bottom pb-4 mb-2"></div>
                <div class="fw-semibold small">Guardian</div>
                <small class="text-muted">Acknowledgment</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .topbar, .app-footer, .bg-orb-3, .sidebar-backdrop {
        display: none !important;
    }
    .app-shell {
        display: block !important;
    }
    .app-main {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .content {
        padding: 0 !important;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .report-card-sheet {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
        padding: 20px !important;
    }
    .table-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
@endsection
