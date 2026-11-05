@extends('Layouts.LayoutApp')
@section('title', 'Timetable Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Class Timetable</h3>
        <small class="text-muted">Schedule weekly class sessions with automatic clash detection across class, teacher, and room.</small>
    </div>
    @if ($selectedClassId)
        @if ($classSubjects->isNotEmpty())
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSlotModal">
                <i class="bi bi-plus-lg me-1"></i> Add Timetable Slot
            </button>
        @else
            <a href="{{ route('admin.class-subjects.index') }}" class="btn btn-outline-warning">
                <i class="bi bi-exclamation-triangle me-1"></i> Assign Subjects First
            </a>
        @endif
    @endif
</div>

@include('Layouts.LayoutErrors')

<!-- Class Filter Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.timetable.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="class_id" class="col-form-label fw-semibold"><i class="bi bi-building me-1"></i> Select Class:</label>
            </div>
            <div class="col-md-4">
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if ($selectedClassId)
    <div class="row g-3">
        @php
            $days = [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
            ];
        @endphp

        @foreach ($days as $dayNum => $dayName)
            @php
                $daySlots = $slots[$dayNum] ?? collect();
            @endphp
            <div class="col-lg">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-transparent py-2 border-bottom text-center">
                        <span class="fw-bold">{{ $dayName }}</span>
                        <span class="badge bg-secondary ms-1">{{ $daySlots->count() }}</span>
                    </div>
                    <div class="card-body p-2 d-flex flex-column gap-2">
                        @forelse ($daySlots as $slot)
                            <div class="p-2 rounded border bg-body-tertiary position-relative">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-bold text-primary">{{ $slot->classSubject->subject->name }}</span>
                                    <form method="POST" action="{{ route('admin.timetable.destroy', $slot) }}" class="m-0" onsubmit="return confirm('Remove this slot?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger p-0" title="Delete Slot"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                                <div class="small text-muted"><i class="bi bi-person me-1"></i>{{ $slot->classSubject->teacher->user->name }}</div>
                                <div class="d-flex justify-content-between align-items-center mt-2 small">
                                    <span class="badge bg-dark-subtle text-body border"><i class="bi bi-clock me-1"></i>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-door-closed me-1"></i>{{ $slot->room }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-calendar-minus d-block fs-4 mb-1"></i>
                                No sessions scheduled
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Slot Modal -->
    <div class="modal fade" id="createSlotModal" tabindex="-1" aria-labelledby="createSlotModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.timetable.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createSlotModalLabel"><i class="bi bi-calendar-plus me-2 text-primary"></i>Schedule Timetable Slot</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modal_subject" class="form-label">Subject & Teacher <span class="text-danger">*</span></label>
                            <select name="class_subject_id" id="modal_subject" class="form-select @error('class_subject_id') is-invalid @enderror" required>
                                <option value="">Select Class Subject</option>
                                @foreach ($classSubjects as $cs)
                                    <option value="{{ $cs->id }}" @selected(old('class_subject_id') == $cs->id)>
                                        {{ $cs->subject->name }} (Teacher: {{ $cs->teacher->user->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('class_subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="modal_day" class="form-label">Day of Week <span class="text-danger">*</span></label>
                            <select name="day_of_week" id="modal_day" class="form-select @error('day_of_week') is-invalid @enderror" required>
                                <option value="1" @selected(old('day_of_week') == 1)>Monday</option>
                                <option value="2" @selected(old('day_of_week') == 2)>Tuesday</option>
                                <option value="3" @selected(old('day_of_week') == 3)>Wednesday</option>
                                <option value="4" @selected(old('day_of_week') == 4)>Thursday</option>
                                <option value="5" @selected(old('day_of_week') == 5)>Friday</option>
                            </select>
                            @error('day_of_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="modal_start" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="modal_start" class="form-control @error('start_time') is-invalid @enderror" required value="{{ old('start_time', '08:30') }}">
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="modal_end" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="modal_end" class="form-control @error('end_time') is-invalid @enderror" required value="{{ old('end_time', '09:30') }}">
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_room" class="form-label">Room / Hall <span class="text-danger">*</span></label>
                            <input type="text" name="room" id="modal_room" class="form-control @error('room') is-invalid @enderror" placeholder="e.g. Room 101, Science Lab A" required value="{{ old('room') }}">
                            @error('room')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="bi bi-shield-check me-1"></i>
                            The system will automatically verify there are no time clashes for this room, teacher, or class.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('createSlotModal');
                if (modalEl) {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
@endif
@endsection
