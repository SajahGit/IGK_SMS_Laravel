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
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Staff member</th><th>Check-in</th><th>Check-out</th><th>Notes</th><th></th></tr></thead>
            <tbody>
            @forelse ($teachers as $teacher)
                @php($record = $attendance->get($teacher->id))
                <tr>
                    <td>{{ $teacher->user->name }}<div class="small text-muted">{{ $teacher->department?->name ?? 'Staff' }}</div></td>
                    <td colspan="4">
                        <form method="POST" action="{{ route('admin.staff-attendance.store') }}" class="row g-2 align-items-center">
                            @csrf
                            <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                            <input type="hidden" name="attendance_date" value="{{ $date }}">
                            <div class="col-6 col-md-2"><input aria-label="Check-in time for {{ $teacher->user->name }}" type="time" name="check_in" value="{{ old('check_in', $record?->check_in) }}" class="form-control form-control-sm" required></div>
                            <div class="col-6 col-md-2"><input aria-label="Check-out time for {{ $teacher->user->name }}" type="time" name="check_out" value="{{ old('check_out', $record?->check_out) }}" class="form-control form-control-sm"></div>
                            <div class="col-8 col-md-5"><input aria-label="Notes for {{ $teacher->user->name }}" type="text" name="notes" value="{{ old('notes', $record?->notes) }}" maxlength="255" class="form-control form-control-sm" placeholder="Notes (optional)"></div>
                            <div class="col-4 col-md-3"><button class="btn btn-sm btn-outline-primary">{{ $record ? 'Update' : 'Save' }}</button></div>
                        </form>
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
