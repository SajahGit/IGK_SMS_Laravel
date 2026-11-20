@extends(($pdfExport ?? false) ? 'Layouts.LayoutPdf' : 'Layouts.LayoutApp')
@section('title', 'Enrollment Register')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Enrollment Register</h3>
        <small class="text-muted">Class offering subject enrollments and detailed student lists.</small>
    </div>
    <div class="no-print">
        <a href="{{ route('reports.pdf', ['report' => 'enrollment'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.enrollment.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    </div>
</div>

@include('Reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-2">
        <label class="form-label visually-hidden">Class</label>
        <select name="class_id" class="form-select">
            <option value="">All classes</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? null) == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="program_id" class="form-select">
            <option value="">All programs</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="term_id" class="form-select">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected(($filters['term_id'] ?? null) == $term->id)>{{ $term->academicYear->name }} — {{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label visually-hidden">Subject</label>
        <select name="subject_id" class="form-select">
            <option value="">All subjects</option>
            @foreach ($subjects as $s)
                <option value="{{ $s->id }}" @selected(($filters['subject_id'] ?? null) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All enrollment statuses</option>
            @foreach (['enrolled', 'dropped', 'completed'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="print-nowrap">Term</th>
                    <th>Program</th>
                    <th class="print-nowrap">Section</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th class="print-nowrap text-center">Student Count</th>
                    <th>Enrolled Students</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="print-nowrap">{{ $row['term'] }}</td>
                    <td>{{ $row['program'] }}</td>
                    <td class="print-nowrap"><strong>{{ $row['class'] }}</strong></td>
                    <td>{{ $row['subject'] }}</td>
                    <td>{{ $row['teacher'] }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-2 py-1">
                            {{ $row['student_count'] }}
                        </span>
                    </td>
                    <td>
                        @if ($pdfExport ?? false)
                            {{ $row['student_list'] }}
                        @else
                        <!-- Screen display: Clickable chips and Detail Modal -->
                        <div class="d-print-none">
                            @if (empty($row['students_data']))
                                <span class="text-muted small">No students enrolled</span>
                            @else
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @foreach (array_slice($row['students_data'], 0, 2) as $st)
                                        <a href="{{ route('admin.students.show', $st['id']) }}" class="badge bg-secondary-subtle text-body border text-decoration-none py-1 px-2" title="Click to view student profile">
                                            <i class="bi bi-person me-1"></i>{{ $st['admission_no'] }} — {{ $st['name'] }}
                                        </a>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#enrollModal{{ $row['class_subject_id'] }}" title="Click to view full student details">
                                        <i class="bi bi-people-fill me-1"></i>View All ({{ $row['student_count'] }})
                                    </button>
                                </div>

                                <!-- Students Detail Modal -->
                                <div class="modal fade" id="enrollModal{{ $row['class_subject_id'] }}" tabindex="-1" aria-labelledby="enrollModalLabel{{ $row['class_subject_id'] }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title mb-0" id="enrollModalLabel{{ $row['class_subject_id'] }}">
                                                        <i class="bi bi-journal-check me-2"></i>{{ $row['subject'] }}
                                                    </h5>
                                                    <div class="small text-muted mt-1">
                                                        <span>Class: <strong>{{ $row['class'] }}</strong></span> &bull;
                                                        <span>Teacher: <strong>{{ $row['teacher'] }}</strong></span> &bull;
                                                        <span>Term: <strong>{{ $row['term'] }}</strong></span>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 50px;">#</th>
                                                                <th>Admission No</th>
                                                                <th>Student Name</th>
                                                                <th>Status</th>
                                                                <th class="text-end">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($row['students_data'] as $idx => $st)
                                                                <tr>
                                                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                                                    <td>
                                                                        <a href="{{ route('admin.students.show', $st['id']) }}" class="fw-semibold text-primary text-decoration-none">
                                                                            <i class="bi bi-person-badge me-1"></i>{{ $st['admission_no'] }}
                                                                        </a>
                                                                    </td>
                                                                    <td>
                                                                        <a href="{{ route('admin.students.show', $st['id']) }}" class="text-body fw-medium text-decoration-none">
                                                                            {{ $st['name'] }}
                                                                        </a>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge {{ $st['status'] === 'enrolled' ? 'bg-success' : 'bg-secondary' }}">
                                                                            {{ ucfirst($st['status']) }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <a href="{{ route('admin.students.show', $st['id']) }}" class="btn btn-sm btn-outline-info">
                                                                            <i class="bi bi-eye me-1"></i>View Profile
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <span class="text-muted small me-auto">Total: {{ count($row['students_data']) }} students enrolled</span>
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <!-- Print fallback -->
                        <div class="d-none d-print-block small">
                            {{ $row['student_list'] }}
                        </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No enrollment data matches these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
