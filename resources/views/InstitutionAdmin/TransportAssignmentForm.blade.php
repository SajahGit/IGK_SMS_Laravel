@extends('Layouts.LayoutApp')
@section('title', $assignment ? 'Edit Transport Assignment' : 'Assign Student Transport')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $assignment ? 'Edit Transport Assignment' : 'Assign Student Transport' }}</h3>
    <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $assignment ? route('admin.transport.assignments.update', $assignment) : route('admin.transport.assignments.store') }}">
        @csrf
        @if ($assignment) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Student <span class="text-danger">*</span></label><select name="student_id" class="form-select" required><option value="">Choose student</option>@foreach ($students as $student)<option value="{{ $student->id }}" @selected((string) old('student_id', $assignment?->student_id) === (string) $student->id)>{{ $student->admission_no }} — {{ $student->user->name }} ({{ $student->class?->full_name ?? 'No class' }})</option>@endforeach</select>@error('student_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Academic year <span class="text-danger">*</span></label><select name="academic_year_id" class="form-select" required><option value="">Choose academic year</option>@foreach ($academicYears as $academicYear)<option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $assignment?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>@endforeach</select>@error('academic_year_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Route <span class="text-danger">*</span></label><select name="transport_route_id" class="form-select" required><option value="">Choose route</option>@foreach ($routes as $transportRoute)<option value="{{ $transportRoute->id }}" @selected((string) old('transport_route_id', $assignment?->transport_route_id) === (string) $transportRoute->id)>{{ $transportRoute->code }} — {{ $transportRoute->name }}{{ $transportRoute->vehicle ? ' / '.$transportRoute->vehicle->registration_number : '' }}</option>@endforeach</select>@error('transport_route_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Pickup / drop-off stop <span class="text-danger">*</span></label><select name="transport_stop_id" class="form-select" required><option value="">Choose stop</option>@foreach ($routes as $transportRoute)@foreach ($transportRoute->stops as $stop)<option value="{{ $stop->id }}" @selected((string) old('transport_stop_id', $assignment?->transport_stop_id) === (string) $stop->id)>{{ $transportRoute->name }} — {{ $stop->sequence }}. {{ $stop->name }}</option>@endforeach @endforeach</select>@error('transport_stop_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Start date <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required value="{{ old('start_date', $assignment?->start_date?->format('Y-m-d') ?? today()->toDateString()) }}">@error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $assignment?->end_date?->format('Y-m-d')) }}">@error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @if ($assignment)
            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $assignment->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $assignment->status) === 'inactive')>Inactive</option></select>@error('status')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            @endif
        </div>
        <div class="mt-4"><button class="btn btn-primary">{{ $assignment ? 'Save Assignment' : 'Assign Student' }}</button> <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
@endsection
