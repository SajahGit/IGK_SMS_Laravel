@extends('Layouts.LayoutApp')
@section('title', 'Staff attendance')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Staff attendance</h3><small class="text-muted">Record daily staff check-in and check-out times.</small></div>
    <form method="GET" action="{{ route('admin.staff-attendance.index') }}" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" class="form-control">
        <button class="btn btn-outline-primary">View date</button>
    </form>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle staff-attendance-table">
            <thead class="table-light">
                <tr>
                    <th class="staff-attendance-col-staff">Staff member</th>
                    <th class="staff-attendance-col-time">Check-in</th>
                    <th class="staff-attendance-col-time">Check-out</th>
                    <th class="staff-attendance-col-notes">Notes</th>
                    <th class="staff-attendance-col-action">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($teachers as $teacher)
                @php($record = $attendance->get($teacher->id))
                @php($formId = 'staff-attendance-'.$teacher->id)
                <tr>
                    <td class="staff-attendance-col-staff">
                        {{ $teacher->user->name }}
                        <div class="small text-muted">{{ $teacher->department?->name ?? 'Staff' }}</div>
                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.staff-attendance.store') }}">
                            @csrf
                            <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                            <input type="hidden" name="attendance_date" value="{{ $date }}">
                        </form>
                    </td>
                    <td class="staff-attendance-col-time">
                        <input form="{{ $formId }}" aria-label="Check-in time for {{ $teacher->user->name }}" type="time" name="check_in" value="{{ old('check_in', $record?->check_in ? substr($record->check_in, 0, 5) : '') }}" class="form-control form-control-sm" required>
                    </td>
                    <td class="staff-attendance-col-time">
                        <input form="{{ $formId }}" aria-label="Check-out time for {{ $teacher->user->name }}" type="time" name="check_out" value="{{ old('check_out', $record?->check_out ? substr($record->check_out, 0, 5) : '') }}" class="form-control form-control-sm">
                    </td>
                    <td class="staff-attendance-col-notes">
                        <input form="{{ $formId }}" aria-label="Notes for {{ $teacher->user->name }}" type="text" name="notes" value="{{ old('notes', $record?->notes) }}" maxlength="255" class="form-control form-control-sm" placeholder="Notes (optional)">
                    </td>
                    <td class="staff-attendance-col-action">
                        <button form="{{ $formId }}" class="btn btn-sm btn-outline-primary">{{ $record ? 'Update' : 'Save' }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No active staff members found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
