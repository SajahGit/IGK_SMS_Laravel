@extends('Layouts.LayoutApp')
@section('title', $vehicle ? 'Edit Transport Vehicle' : 'Add Transport Vehicle')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $vehicle ? 'Edit Transport Vehicle' : 'Add Transport Vehicle' }}</h3>
    <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $vehicle ? route('admin.transport.vehicles.update', $vehicle) : route('admin.transport.vehicles.store') }}">
        @csrf
        @if ($vehicle) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Registration number <span class="text-danger">*</span></label><input name="registration_number" class="form-control" required maxlength="30" value="{{ old('registration_number', $vehicle?->registration_number) }}">@error('registration_number')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Vehicle type <span class="text-danger">*</span></label><input name="vehicle_type" class="form-control" required maxlength="40" placeholder="School bus, minibus" value="{{ old('vehicle_type', $vehicle?->vehicle_type) }}">@error('vehicle_type')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Passenger capacity <span class="text-danger">*</span></label><input type="number" min="1" max="500" name="capacity" class="form-control" required value="{{ old('capacity', $vehicle?->capacity ?? 40) }}">@error('capacity')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Driver name <span class="text-danger">*</span></label><input name="driver_name" class="form-control" required maxlength="120" value="{{ old('driver_name', $vehicle?->driver_name) }}">@error('driver_name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Driver phone</label><input name="driver_phone" class="form-control" maxlength="30" value="{{ old('driver_phone', $vehicle?->driver_phone) }}">@error('driver_phone')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Insurance expiry</label><input type="date" name="insurance_expiry" class="form-control" value="{{ old('insurance_expiry', $vehicle?->insurance_expiry?->format('Y-m-d')) }}">@error('insurance_expiry')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Inspection expiry</label><input type="date" name="inspection_expiry" class="form-control" value="{{ old('inspection_expiry', $vehicle?->inspection_expiry?->format('Y-m-d')) }}">@error('inspection_expiry')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required>@foreach (['active' => 'Active', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $vehicle?->status ?? 'active') === $value)>{{ $label }}</option>@endforeach</select>@error('status')<div class="text-danger small">{{ $message }}</div>@enderror</div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">{{ $vehicle ? 'Save Changes' : 'Create Vehicle' }}</button> <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
@endsection
