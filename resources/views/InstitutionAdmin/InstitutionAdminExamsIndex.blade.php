@extends('Layouts.LayoutApp')
@section('title', 'Exams & Results')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Exams & Results</h3>
        <small class="text-muted">Review exam marks, enforce grading completion, and publish official student results.</small>
    </div>
</div>

@include('Layouts.LayoutErrors')

<div class="d-flex justify-content-end mb-3">
    @can('exams.create')
        <a href="{{ route('admin.exams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create exam</a>
    @endcan
</div>

<!-- Search & Filter Controls -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.exams.index') }}" class="row g-2 align-items-end" id="examFilterForm">
            <!-- Search Query -->
            <div class="col-12 col-md-4 col-lg-3">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-search me-1"></i>Search Exams
                </label>
                <div class="input-group">
                    <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Exam name, subject, code..." autocomplete="off">
                    @if ($search)
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('search').value=''; document.getElementById('examFilterForm').submit();" title="Clear search">
                            <i class="bi bi-x"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Class Filter -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="class_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-building me-1"></i>Class
                </label>
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subject Filter -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="subject_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-book me-1"></i>Subject
                </label>
                <select name="subject_id" id="subject_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}" @selected(($subjectId ?? null) == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Term Filter -->
            <div class="col-6 col-md-3 col-lg-2">
                <label for="term_id" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-calendar2-week me-1"></i>Term
                </label>
                <select name="term_id" id="term_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Terms</option>
                    @foreach ($terms as $t)
                        <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }} {{ $t->is_current ? '(Active)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Exam Type Filter -->
            <div class="col-6 col-md-3 col-lg-1">
                <label for="type" class="form-label small fw-semibold text-muted mb-1">Type</label>
                <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach ($examTypes as $tVal)
                        <option value="{{ $tVal }}" @selected(($type ?? null) === $tVal)>{{ ucfirst($tVal) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Publication Status Filter -->
            <div class="col-6 col-md-3 col-lg-2">
                <label for="status" class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-globe me-1"></i>Status
                </label>
                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="published" @selected(($status ?? null) === 'published')>Published Only</option>
                    <option value="draft" @selected(($status ?? null) === 'draft')>Draft / Locked</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-3 col-lg-auto d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary" title="Apply Search">
                    <i class="bi bi-funnel me-1"></i>Search
                </button>
                @if ($search || $classId || $termId || ($subjectId ?? null) || ($type ?? null) || ($status ?? null))
                    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary" title="Clear All Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Exam Details</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Exam Date</th>
                    <th>Marks Status</th>
                    <th>Publication</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($exams as $exam)
                    @php
                        $enrolledCount = $exam->class->students()->count();
                        $recordedCount = $exam->results_count;
                        $isComplete = $enrolledCount > 0 && $recordedCount >= $enrolledCount;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $exam->name }}</div>
                            <small class="text-muted text-uppercase">Type: {{ $exam->type }} &middot; Max: {{ $exam->max_marks }} &middot; Weight: {{ $exam->weight }}%</small>
                        </td>
                        <td>{{ $exam->class?->full_name ?? 'Unassigned' }}</td>
                        <td>{{ $exam->subject->name }}</td>
                        <td>{{ $exam->term ? $exam->term->name : '—' }}</td>
                        <td>{{ $exam->exam_date->format('d M Y') }}</td>
                        <td>
                            @if ($isComplete)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check2-all me-1"></i> {{ $recordedCount }}/{{ $enrolledCount }} graded
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-clock me-1"></i> {{ $recordedCount }}/{{ $enrolledCount }} graded
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($exam->is_published)
                                <span class="badge bg-success"><i class="bi bi-globe me-1"></i> Published</span>
                                <div class="small text-muted">{{ $exam->published_at ? $exam->published_at->format('d M, h:i A') : '' }}</div>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i> Draft / Locked</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.exams.results', $exam) }}" class="btn btn-outline-primary" title="View Results">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if (!$exam->is_published)
                                    @can('exams.update')
                                        <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-outline-secondary" title="Edit exam"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('exams.delete')
                                        <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" onsubmit="return confirm('Archive this exam? Existing result records will remain preserved.')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" title="Archive exam"><i class="bi bi-archive"></i></button>
                                        </form>
                                    @endcan
                                @endif
                                @if (!$exam->is_published)
                                    <form method="POST" action="{{ route('admin.exams.publish', $exam) }}" class="d-inline" onsubmit="return confirm('Publish results for {{ $exam->name }}? Students and guardians will now be able to view results.')">
                                        @csrf
                                        <button class="btn btn-outline-success" {{ !$isComplete ? 'disabled' : '' }} title="{{ !$isComplete ? 'All enrolled students must be graded or marked absent before publishing' : 'Publish Results' }}">
                                            <i class="bi bi-send-check me-1"></i> Publish
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.exams.unpublish', $exam) }}" class="d-inline-flex align-items-center gap-1" onsubmit="return confirm('Unpublish results for {{ $exam->name }}? Marks will be unlocked for editing and hidden from students.')">
                                        @csrf
                                        <input name="reason" class="form-control form-control-sm" required minlength="5" maxlength="1000" placeholder="Reason" aria-label="Reason for unpublishing">
                                        <button class="btn btn-outline-warning" title="Unpublish & Unlock Marks">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Unpublish
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                            No exams match the selected search or filter criteria.
                            @if ($search || $classId || $termId || ($subjectId ?? null) || ($type ?? null) || ($status ?? null))
                                <div class="mt-2">
                                    <a href="{{ route('admin.exams.index') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Search &amp; Filters
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $exams->links('pagination::bootstrap-5') }}</div>
@endsection
