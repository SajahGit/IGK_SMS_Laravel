@extends('Layouts.LayoutApp')
@section('title', 'Assign Student Hostel')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $assignment ? 'Edit Hostel Assignment' : 'Assign Student Hostel' }}</h3>
    <a href="{{ route('admin.hostels.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $assignment ? route('admin.hostels.assignments.update', $assignment) : route('admin.hostels.assignments.store') }}">
        @csrf
        @if ($assignment) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label for="student_id" class="form-label">Student</label>
                <select id="student_id" name="student_id" class="form-select" required>
                    <option value="">Choose student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected((string) old('student_id', $assignment?->student_id) === (string) $student->id)>{{ $student->admission_no }} — {{ $student->user->name }}</option>
                    @endforeach
                </select>
                @error('student_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="hostel_room_id" class="form-label">Hostel / Room</label>
                <select id="hostel_room_id" name="hostel_room_id" class="form-select" required>
                    <option value="">Choose room</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('hostel_room_id', $assignment?->hostel_room_id) === (string) $room->id)>{{ $room->hostel->name }} — Room {{ $room->room_number }} ({{ $room->bed_capacity }} beds)</option>
                    @endforeach
                </select>
                @error('hostel_room_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="bed_number" class="form-label">Bed number</label>
                <input id="bed_number" name="bed_number" type="number" min="1" max="100" value="{{ old('bed_number', $assignment?->bed_number ?? 1) }}" class="form-control" required>
                @error('bed_number')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="academic_year_id" class="form-label">Academic year</label>
                <select id="academic_year_id" name="academic_year_id" class="form-select" required>
                    <option value="">Choose academic year</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $assignment?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>
                @error('academic_year_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start date</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $assignment?->start_date?->toDateString() ?? today()->toDateString()) }}" class="form-control" required>
                @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="end_date" class="form-label">End date</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $assignment?->end_date?->toDateString()) }}" class="form-control">
                @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
        @if ($assignment)
            <div class="mt-3"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select"><option value="active" @selected(old('status', $assignment->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $assignment->status) === 'inactive')>Inactive</option></select></div>
        @endif
        <div class="mt-4"><button class="btn btn-primary">{{ $assignment ? 'Save Assignment' : 'Assign Bed' }}</button> <a href="{{ route('admin.hostels.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
@endsection
