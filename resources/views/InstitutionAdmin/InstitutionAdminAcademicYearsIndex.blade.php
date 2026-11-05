@extends('Layouts.LayoutApp')
@section('title', 'Academic Years')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Academic Years</h3>
        <small class="text-muted">Manage school calendar cycles, terms, and active academic year.</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createYearModal">
        <i class="bi bi-plus-lg me-1"></i> Add Academic Year
    </button>
</div>

@include('Layouts.LayoutErrors')

@php
    $currentYear = $academicYears->firstWhere('is_current', true);
    $totalTerms = $academicYears->sum('terms_count');
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'calendar-range',
            'value' => $academicYears->count(),
            'label' => 'Total Academic Years',
            'tone' => 'primary',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'check-circle-fill',
            'value' => $currentYear ? $currentYear->name : 'None Set',
            'label' => 'Current Active Year',
            'tone' => $currentYear ? 'success' : 'warning',
        ])
    </div>
    <div class="col-sm-6 col-lg-4">
        @include('Layouts.LayoutStat', [
            'icon' => 'calendar2-week',
            'value' => $totalTerms,
            'label' => 'Total Configured Terms',
            'tone' => 'info',
        ])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Academic Year</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Terms</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($academicYears as $year)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $year->name }}</div>
                            @if ($year->is_current)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-star-fill me-1"></i> Current Active
                                </span>
                            @endif
                        </td>
                        <td>{{ $year->start_date ? $year->start_date->format('d M Y') : '—' }}</td>
                        <td>{{ $year->end_date ? $year->end_date->format('d M Y') : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.terms.index', ['academic_year_id' => $year->id]) }}" class="badge bg-secondary text-decoration-none">
                                {{ $year->terms_count }} {{ Str::plural('term', $year->terms_count) }}
                            </a>
                        </td>
                        <td>
                            @if ($year->is_current)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if (!$year->is_current)
                                    <form method="POST" action="{{ route('admin.academic-years.set-current', $year) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-outline-success" title="Set as Current Active Year" onclick="return confirm('Set {{ $year->name }} as the current active academic year?')">
                                            <i class="bi bi-check2-circle me-1"></i> Set Active
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-outline-primary edit-year-btn"
                                        data-id="{{ $year->id }}"
                                        data-name="{{ $year->name }}"
                                        data-start="{{ $year->start_date ? $year->start_date->format('Y-m-d') : '' }}"
                                        data-end="{{ $year->end_date ? $year->end_date->format('Y-m-d') : '' }}"
                                        data-current="{{ $year->is_current ? 1 : 0 }}"
                                        data-action="{{ route('admin.academic-years.update', $year) }}">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('admin.academic-years.destroy', $year) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete academic year \'{{ $year->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" {{ $year->terms_count > 0 ? 'disabled' : '' }} title="{{ $year->terms_count > 0 ? 'Cannot delete year with terms' : 'Delete academic year' }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                            No academic years defined yet. Click "Add Academic Year" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createYearModal" tabindex="-1" aria-labelledby="createYearModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.academic-years.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createYearModalLabel"><i class="bi bi-calendar-plus me-2"></i>Add Academic Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Name / Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_name" name="name" placeholder="e.g. 2026/2027 or 2026" required value="{{ old('name') }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="create_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="create_start_date" name="start_date" required value="{{ old('start_date') }}">
                        </div>
                        <div class="col-6">
                            <label for="create_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="create_end_date" name="end_date" required value="{{ old('end_date') }}">
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="create_is_current" name="is_current" value="1" {{ old('is_current') ? 'checked' : '' }}>
                        <label class="form-check-label" for="create_is_current">
                            Set as Current Active Year
                        </label>
                        <div class="form-text small text-muted">Setting this to active will automatically deactivate other academic years.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Academic Year</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editYearModal" tabindex="-1" aria-labelledby="editYearModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editYearForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editYearModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Academic Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name / Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_is_current" name="is_current" value="1">
                        <label class="form-check-label" for="edit_is_current">
                            Set as Current Active Year
                        </label>
                        <div class="form-text small text-muted">Setting this to active will automatically deactivate other academic years.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Academic Year</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editModalEl = document.getElementById('editYearModal');
        if (!editModalEl) return;
        var editModal = new bootstrap.Modal(editModalEl);
        var form = document.getElementById('editYearForm');
        var nameInput = document.getElementById('edit_name');
        var startInput = document.getElementById('edit_start_date');
        var endInput = document.getElementById('edit_end_date');
        var currentInput = document.getElementById('edit_is_current');

        document.querySelectorAll('.edit-year-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = this.dataset.action;
                nameInput.value = this.dataset.name;
                startInput.value = this.dataset.start;
                endInput.value = this.dataset.end;
                currentInput.checked = this.dataset.current === '1';
                editModal.show();
            });
        });
    });
</script>
@endsection
