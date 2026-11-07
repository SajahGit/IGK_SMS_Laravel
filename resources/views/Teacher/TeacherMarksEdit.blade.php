@extends('Layouts.LayoutApp')
@section('title', 'Enter Marks - ' . $exam->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Enter Marks</h3>
        <p class="text-muted mb-0">
            <strong>{{ $exam->name }}</strong> &middot; {{ $exam->class->full_name }} &middot; {{ $exam->subject->name }}
            @if ($exam->term)
                &middot; <span class="badge bg-secondary-subtle text-secondary">{{ $exam->term->name }}</span>
            @endif
            &middot; Max: <strong>{{ $exam->max_marks }}</strong> &middot; Weight: <strong>{{ $exam->weight }}%</strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('teacher.marks.show', $exam) }}" class="btn btn-outline-primary">
            <i class="bi bi-eye me-1"></i> View Results
        </a>
        <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Back to Exams</a>
    </div>
</div>

@include('Layouts.LayoutErrors')

@if ($exam->is_published)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-lock-fill fs-5"></i>
        <div>
            <strong>Locked:</strong> These exam results have been published by the administration. Marks cannot be modified unless unpublished.
        </div>
    </div>
@endif

<form method="POST" action="{{ route('teacher.marks.update', $exam) }}">
    @csrf
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Admission No</th>
                        <th>Student Name</th>
                        <th style="width: 15%;" class="text-center">Absent?</th>
                        <th style="width: 15%;" class="text-center">Excused?</th>
                        <th style="width: 25%;">Marks (Max: {{ $exam->max_marks }})</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($students as $s)
                    @php
                        $record = $existing[$s->id] ?? null;
                        $isAbsent = old('is_absent.'.$s->id, $record?->is_absent ?? false);
                        $isExcused = old('is_excused.'.$s->id, $record?->is_excused ?? false);
                        $markVal = old('marks.'.$s->id, ($record && !$record->is_absent && $record->marks !== null) ? \App\Support\SchoolHelper::formatMarks($record->marks) : '');
                    @endphp
                    <tr>
                        <td><code>{{ $s->admission_no }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $s->user->name }}</div>
                        </td>
                        <td class="text-center">
                            <div class="form-check d-inline-block">
                                <input type="checkbox" name="is_absent[{{ $s->id }}]" value="1"
                                       class="form-check-input absent-toggle"
                                       id="absent_{{ $s->id }}"
                                       data-target="mark_{{ $s->id }}"
                                       data-excused-target="excused_{{ $s->id }}"
                                       @checked($isAbsent)
                                       {{ $exam->is_published ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="absent_{{ $s->id }}">Absent</label>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="form-check d-inline-block">
                                <input type="checkbox" name="is_excused[{{ $s->id }}]" value="1"
                                       class="form-check-input excused-toggle"
                                       id="excused_{{ $s->id }}"
                                       @checked($isExcused)
                                       {{ !$isAbsent || $exam->is_published ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="excused_{{ $s->id }}">Excused</label>
                            </div>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $exam->max_marks }}"
                                   name="marks[{{ $s->id }}]"
                                   id="mark_{{ $s->id }}"
                                   value="{{ $markVal }}"
                                   class="form-control"
                                   placeholder="Marks (0 - {{ $exam->max_marks }})"
                                   {{ $isAbsent || $exam->is_published ? 'disabled' : '' }}>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No students currently enrolled in this class.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($students->isNotEmpty() && !$exam->is_published)
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-all me-1"></i> Save Marks</button>
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    @endif
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.absent-toggle').forEach(function (chk) {
            chk.addEventListener('change', function () {
                var markInput = document.getElementById(this.dataset.target);
                var excusedInput = document.getElementById(this.dataset.excusedTarget);
                if (!markInput) return;
                if (this.checked) {
                    markInput.value = '';
                    markInput.disabled = true;
                } else {
                    markInput.disabled = false;
                    if (excusedInput) {
                        excusedInput.checked = false;
                        excusedInput.disabled = true;
                    }
                }
                if (excusedInput && this.checked) {
                    excusedInput.disabled = false;
                }
            });
        });
    });
</script>
@endsection
