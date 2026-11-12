@extends('Layouts.LayoutApp')
@section('title', $transportRoute ? 'Manage Transport Route' : 'Add Transport Route')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $transportRoute ? 'Manage Transport Route' : 'Add Transport Route' }}</h3>
    <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0 mb-4"><div class="card-body">
    <form method="POST" action="{{ $transportRoute ? route('admin.transport.routes.update', $transportRoute) : route('admin.transport.routes.store') }}">
        @csrf
        @if ($transportRoute) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Route code <span class="text-danger">*</span></label><input name="code" class="form-control" required maxlength="30" value="{{ old('code', $transportRoute?->code) }}">@error('code')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-5"><label class="form-label">Route name <span class="text-danger">*</span></label><input name="name" class="form-control" required maxlength="120" value="{{ old('name', $transportRoute?->name) }}">@error('name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Vehicle</label><select name="vehicle_id" class="form-select"><option value="">Not assigned</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $transportRoute?->vehicle_id) === (string) $vehicle->id)>{{ $vehicle->registration_number }} — {{ $vehicle->capacity }} seats ({{ $vehicle->status }})</option>@endforeach</select>@error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Monthly fee</label><input type="number" step="0.01" min="0" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', $transportRoute?->monthly_fee ?? '0.00') }}">@error('monthly_fee')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-8"><label class="form-label">Notes</label><textarea name="notes" rows="2" maxlength="5000" class="form-control">{{ old('notes', $transportRoute?->notes) }}</textarea>@error('notes')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $transportRoute?->is_active ?? true))> Active route</label></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">{{ $transportRoute ? 'Save Route' : 'Create Route' }}</button> <a href="{{ route('admin.transport.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>

@if ($transportRoute)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Route Stops</h5></div>
    <div class="card-body">
        @can('transport.update')
        <form method="POST" action="{{ route('admin.transport.stops.store', $transportRoute) }}" class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-md-3"><label class="form-label">Stop name</label><input name="name" class="form-control" required maxlength="120" value="{{ old('name') }}"></div>
            <div class="col-md-2"><label class="form-label">Order</label><input name="sequence" type="number" class="form-control" required min="1" max="500" value="{{ old('sequence') }}"></div>
            <div class="col-md-3"><label class="form-label">Address</label><input name="address" class="form-control" maxlength="500" value="{{ old('address') }}"></div>
            <div class="col-md-2"><label class="form-label">Pickup</label><input name="pickup_time" type="time" class="form-control" value="{{ old('pickup_time') }}"></div>
            <div class="col-md-2"><label class="form-label">Drop-off</label><input name="dropoff_time" type="time" class="form-control" value="{{ old('dropoff_time') }}"></div>
            <div class="col-12"><button class="btn btn-outline-primary">Add Stop</button></div>
        </form>
        @endcan
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Order</th><th>Stop</th><th>Address</th><th>Pickup</th><th>Drop-off</th><th>Assigned students</th><th></th></tr></thead>
            <tbody>
            @forelse ($transportRoute->stops as $stop)
                <tr>
                    <td><form id="stop-update-{{ $stop->id }}" method="POST" action="{{ route('admin.transport.stops.update', [$transportRoute, $stop]) }}">@csrf @method('PUT')</form><input form="stop-update-{{ $stop->id }}" name="sequence" type="number" class="form-control form-control-sm" required min="1" max="500" value="{{ $stop->sequence }}" @cannot('transport.update') disabled @endcannot></td>
                    <td><input form="stop-update-{{ $stop->id }}" name="name" class="form-control form-control-sm" required maxlength="120" value="{{ $stop->name }}" @cannot('transport.update') disabled @endcannot></td>
                    <td><input form="stop-update-{{ $stop->id }}" name="address" class="form-control form-control-sm" maxlength="500" value="{{ $stop->address }}" @cannot('transport.update') disabled @endcannot></td>
                    <td><input form="stop-update-{{ $stop->id }}" name="pickup_time" type="time" class="form-control form-control-sm" value="{{ $stop->pickup_time ? substr($stop->pickup_time, 0, 5) : '' }}" @cannot('transport.update') disabled @endcannot></td>
                    <td><input form="stop-update-{{ $stop->id }}" name="dropoff_time" type="time" class="form-control form-control-sm" value="{{ $stop->dropoff_time ? substr($stop->dropoff_time, 0, 5) : '' }}" @cannot('transport.update') disabled @endcannot></td>
                    <td>{{ $stop->assignments->where('status', 'active')->count() }}</td>
                    <td class="text-end">
                        @can('transport.update')
                        <button form="stop-update-{{ $stop->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                        <form method="POST" action="{{ route('admin.transport.stops.destroy', [$transportRoute, $stop]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No stops have been added to this route.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endif
@endsection
