@extends('Layouts.LayoutApp')
@section('title', 'Exams')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">My Exams</h3>
        <small class="text-muted">Manage classroom exams, grade student submissions, and view results.</small>
    </div>
    <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Exam</a>
</div>

<!-- Search & Filter Controls -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('teacher.exams.index') }}" class="row g-2 align-items-end" id="teacherExamFilterForm">
            <!-- Search Query -->
            <div class="col-12 col-md-5 col-lg-4">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-search me-1"></i>Search Exams
                </label>
                <div class="input-group">
                    <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search by exam, subject, or class..." autocomplete="off">
                    @if ($search)
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('search').value=''; document.getElementById('teacherExamFilterForm').submit();" title="Clear search">
                            <i class="bi bi-x"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Class Filter -->
            <div class="col-6 col-md-4 col-lg-3">
                <label for="class_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-building me-1"></i>Class
                </label>
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All My Classes</option>
                    @foreach ($teacherClasses as $c)
                        <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subject Filter -->
            <div class="col-6 col-md-3 col-lg-3">
                <label for="subject_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-book me-1"></i>Subject
                </label>
                <select name="subject_id" id="subject_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    @foreach ($teacherSubjects as $s)
                        <option value="{{ $s->id }}" @selected($subjectId == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="col-12 col-lg-auto d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary" title="Search Exams">
                    <i class="bi bi-funnel me-1"></i>Search
                </button>
                @if ($search || $classId || $subjectId)
                    <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light"><tr><th>Exam</th><th>Class</th><th>Subject</th><th>Date</th><th>Max</th><th>Marks Entered</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($exams as $e)
            <tr>
                <td><strong>{{ $e->name }}</strong></td>
                <td>{{ $e->class?->full_name ?? 'Unassigned' }}</td>
                <td>{{ $e->subject?->name ?? 'Unassigned' }}</td>
                <td>{{ $e->exam_date->format('d M Y') }}</td>
                <td>{{ $e->max_marks }}</td>
                <td>
                    <span class="badge bg-secondary-subtle text-body border">
                        {{ $e->results_count }} entered
                    </span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('teacher.marks.edit', $e) }}" class="btn btn-outline-primary" title="Enter / Modify Marks">
                            <i class="bi bi-pencil-square me-1"></i>Enter Marks
                        </a>
                        <a href="{{ route('teacher.marks.show', $e) }}" class="btn btn-outline-secondary" title="View Results Summary">
                            <i class="bi bi-eye me-1"></i>Results
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                    No exams found matching your search and filter criteria.
                    @if ($search || $classId || $subjectId)
                        <div class="mt-2">
                            <a href="{{ route('teacher.exams.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Search
                            </a>
                        </div>
                    @endif
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $exams->links('pagination::bootstrap-5') }}</div>
@endsection
