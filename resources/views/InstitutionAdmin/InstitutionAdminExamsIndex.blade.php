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

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.exams.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label for="class_id" class="form-label small fw-semibold">Class</label>
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="term_id" class="form-label small fw-semibold">Term</label>
                <select name="term_id" id="term_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Terms</option>
                    @foreach ($terms as $t)
                        <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }} {{ $t->is_current ? '(Active)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            @if ($classId || $termId)
                <div class="col-auto align-self-end">
                    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Clear Filter</a>
                </div>
            @endif
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
                        <td>{{ $exam->class->full_name }}</td>
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
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-2 d-block mb-1"></i>
                            No exams match the selected filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $exams->links('pagination::bootstrap-5') }}</div>
@endsection
