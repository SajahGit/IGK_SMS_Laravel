@extends('Layouts.LayoutApp')
@section('title', 'Academic Terms')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Academic Terms</h3>
        <small class="text-muted">Manage grading terms, semesters, and sequence within academic years.</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if ($academicYears->isNotEmpty())
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTermModal">
                <i class="bi bi-plus-lg me-1"></i> Add Term
            </button>
        @else
            <a href="{{ route('admin.academic-years.index') }}" class="btn btn-warning">
                <i class="bi bi-exclamation-triangle me-1"></i> Create Academic Year First
            </a>
        @endif
    </div>
</div>

@include('Layouts.LayoutErrors')

@php
    $currentTerm = $terms->firstWhere('is_current', true);
    $totalExams = $terms->sum('exams_count');
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'calendar2-week',
            'value' => $terms->count(),
            'label' => 'Total Terms Listed',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check-circle-fill',
            'value' => $currentTerm ? ($currentTerm->academicYear->name . ' - ' . $currentTerm->name) : 'None Set',
            'label' => 'Current Active Term',
            'tone' => $currentTerm ? 'success' : 'warning',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'journal-check',
            'value' => $totalExams,
            'label' => 'Exams Linked to Terms',
            'tone' => 'info',
        ])
    </div>
</div>

<!-- Filter card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.terms.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="filter_year" class="col-form-label fw-semibold"><i class="bi bi-funnel me-1"></i> Filter by Academic Year:</label>
            </div>
            <div class="col-md-4">
                <select name="academic_year_id" id="filter_year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Academic Years</option>
                    @foreach ($academicYears as $y)
                        <option value="{{ $y->id }}" @selected($selectedYearId == $y->id)>
                            {{ $y->name }} {{ $y->is_current ? '(Current Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($selectedYearId)
                <div class="col-auto">
                    <a href="{{ route('admin.terms.index') }}" class="btn btn-outline-secondary">Clear Filter</a>
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
                    <th>Academic Year</th>
                    <th>Term Name</th>
                    <th>Sequence</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Exams</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($terms as $t)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $t->academicYear->name }}</div>
                            @if ($t->academicYear->is_current)
                                <small class="text-success"><i class="bi bi-check-circle me-1"></i>Active Year</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $t->name }}</span>
                            @if ($t->is_current)
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                                    <i class="bi bi-star-fill me-1"></i> Current
                                </span>
                            @endif
                        </td>
                        <td><span class="badge bg-dark">#{{ $t->sequence }}</span></td>
                        <td>{{ $t->start_date ? $t->start_date->format('d M Y') : '—' }}</td>
                        <td>{{ $t->end_date ? $t->end_date->format('d M Y') : '—' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $t->exams_count }} exams</span></td>
                        <td>
                            @if ($t->is_current)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if (!$t->is_current)
                                    <form method="POST" action="{{ route('admin.terms.set-current', $t) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-outline-success" title="Set as Current Active Term" onclick="return confirm('Set {{ $t->name }} as current active term?')">
                                            <i class="bi bi-check2-circle me-1"></i> Set Active
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-outline-primary edit-term-btn"
                                        data-id="{{ $t->id }}"
                                        data-year="{{ $t->academic_year_id }}"
                                        data-name="{{ $t->name }}"
                                        data-sequence="{{ $t->sequence }}"
                                        data-start="{{ $t->start_date ? $t->start_date->format('Y-m-d') : '' }}"
                                        data-end="{{ $t->end_date ? $t->end_date->format('Y-m-d') : '' }}"
                                        data-current="{{ $t->is_current ? 1 : 0 }}"
                                        data-action="{{ route('admin.terms.update', $t) }}">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('admin.terms.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete term \'{{ $t->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" {{ $t->exams_count > 0 ? 'disabled' : '' }} title="{{ $t->exams_count > 0 ? 'Cannot delete term with exams' : 'Delete term' }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar2-x fs-2 d-block mb-2"></i>
                            No terms defined yet. Click "Add Term" to create a term for an academic year.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Term Modal -->
<div class="modal fade" id="createTermModal" tabindex="-1" aria-labelledby="createTermModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.terms.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createTermModalLabel"><i class="bi bi-calendar-plus me-2"></i>Add Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_term_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="create_term_year" name="academic_year_id" required>
                            <option value="">Select Academic Year</option>
                            @foreach ($academicYears as $y)
                                <option value="{{ $y->id }}" @selected(old('academic_year_id', $selectedYearId) == $y->id)>
                                    {{ $y->name }} {{ $y->is_current ? '(Active Year)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label for="create_term_name" class="form-label">Term Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create_term_name" name="name" placeholder="e.g. Term 1 or First Semester" required value="{{ old('name') }}">
                        </div>
                        <div class="col-4">
                            <label for="create_term_seq" class="form-label">Sequence <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="create_term_seq" name="sequence" min="1" max="10" required value="{{ old('sequence', 1) }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="create_term_start" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="create_term_start" name="start_date" required value="{{ old('start_date') }}">
                        </div>
                        <div class="col-6">
                            <label for="create_term_end" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="create_term_end" name="end_date" required value="{{ old('end_date') }}">
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="create_term_current" name="is_current" value="1" {{ old('is_current') ? 'checked' : '' }}>
                        <label class="form-check-label" for="create_term_current">
                            Set as Current Active Term
                        </label>
                        <div class="form-text small text-muted">Setting this to active will automatically deactivate other terms.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Term</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Term Modal -->
<div class="modal fade" id="editTermModal" tabindex="-1" aria-labelledby="editTermModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editTermForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editTermModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_term_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_term_year" name="academic_year_id" required>
                            @foreach ($academicYears as $y)
                                <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_current ? '(Active Year)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label for="edit_term_name" class="form-label">Term Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_term_name" name="name" required>
                        </div>
                        <div class="col-4">
                            <label for="edit_term_seq" class="form-label">Sequence <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_term_seq" name="sequence" min="1" max="10" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_term_start" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_term_start" name="start_date" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_term_end" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_term_end" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_term_current" name="is_current" value="1">
                        <label class="form-check-label" for="edit_term_current">
                            Set as Current Active Term
                        </label>
                        <div class="form-text small text-muted">Setting this to active will automatically deactivate other terms.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Term</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editModalEl = document.getElementById('editTermModal');
        if (!editModalEl) return;
        var editModal = new bootstrap.Modal(editModalEl);
        var form = document.getElementById('editTermForm');
        var yearSelect = document.getElementById('edit_term_year');
        var nameInput = document.getElementById('edit_term_name');
        var seqInput = document.getElementById('edit_term_seq');
        var startInput = document.getElementById('edit_term_start');
        var endInput = document.getElementById('edit_term_end');
        var currentInput = document.getElementById('edit_term_current');

        document.querySelectorAll('.edit-term-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = this.dataset.action;
                yearSelect.value = this.dataset.year;
                nameInput.value = this.dataset.name;
                seqInput.value = this.dataset.sequence;
                startInput.value = this.dataset.start;
                endInput.value = this.dataset.end;
                currentInput.checked = this.dataset.current === '1';
                editModal.show();
            });
        });
    });
</script>
@endsection
