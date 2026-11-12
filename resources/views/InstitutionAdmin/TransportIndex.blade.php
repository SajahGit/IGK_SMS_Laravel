@extends('Layouts.LayoutApp')
@section('title', 'Transport')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Transport</h3>
        <small class="text-muted">Manage school vehicles, routes, stops, and student assignments.</small>
    </div>
    <div class="d-flex gap-2">
        @can('transport.create')
        <a href="{{ route('admin.transport.vehicles.create') }}" class="btn btn-outline-primary">Add Vehicle</a>
        <a href="{{ route('admin.transport.routes.create') }}" class="btn btn-primary">Add Route</a>
        @endcan
        @can('transport.assign')
        <a href="{{ route('admin.transport.assignments.create') }}" class="btn btn-success">Assign Student</a>
        @endcan
    </div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Routes</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Route</th><th>Vehicle</th><th>Stops</th><th>Active students</th><th>Monthly fee</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($routes as $transportRoute)
                <tr>
                    <td>{{ $transportRoute->code }}</td>
                    <td>{{ $transportRoute->name }}</td>
                    <td>{{ $transportRoute->vehicle?->registration_number ?? 'Not assigned' }}</td>
                    <td>{{ $transportRoute->stops->count() }}</td>
                    <td>{{ $transportRoute->active_assignments }}</td>
                    <td>{{ number_format((float) $transportRoute->monthly_fee, 2) }}</td>
                    <td>@if ($transportRoute->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                    <td class="text-end">
                        @can('transport.read')
                        <a href="{{ route('admin.transport.routes.edit', $transportRoute) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                        @endcan
                        @can('transport.delete')
                        <form method="POST" action="{{ route('admin.transport.routes.destroy', $transportRoute) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No transport routes have been added.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Vehicles</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Registration</th><th>Type</th><th>Capacity</th><th>Driver</th><th>Insurance expiry</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle->registration_number }}</td>
                    <td>{{ $vehicle->vehicle_type }}</td>
                    <td>{{ $vehicle->capacity }}</td>
                    <td>{{ $vehicle->driver_name }}{{ $vehicle->driver_phone ? ' · '.$vehicle->driver_phone : '' }}</td>
                    <td>{{ $vehicle->insurance_expiry?->format('d M Y') ?? 'Not recorded' }}</td>
                    <td><span class="badge {{ $vehicle->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($vehicle->status) }}</span></td>
                    <td class="text-end">
                        @can('transport.read')
                        <a href="{{ route('admin.transport.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @endcan
                        @can('transport.delete')
                        <form method="POST" action="{{ route('admin.transport.vehicles.destroy', $vehicle) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No transport vehicles have been added.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent"><h5 class="mb-0">Student Assignments</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Student</th><th>Route / Stop</th><th>Academic year</th><th>Start</th><th>End</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->student?->user?->name ?? $assignment->student?->admission_no ?? 'Unknown student' }}<div class="small text-muted">{{ $assignment->student?->admission_no ?? '—' }}</div></td>
                    <td>{{ $assignment->route?->name ?? 'Unknown route' }}<div class="small text-muted">{{ $assignment->stop?->name ?? 'Unknown stop' }}</div></td>
                    <td>{{ $assignment->academicYear?->name ?? '—' }}</td>
                    <td>{{ $assignment->start_date->format('d M Y') }}</td>
                    <td>{{ $assignment->end_date?->format('d M Y') ?? '—' }}</td>
                    <td><span class="badge {{ $assignment->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($assignment->status) }}</span></td>
                    <td class="text-end">
                        @can('transport.assign')
                        <a href="{{ route('admin.transport.assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @if ($assignment->status === 'active')
                        <form method="POST" action="{{ route('admin.transport.assignments.end', $assignment) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">End Assignment</button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No student transport assignments have been recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $assignments->links('pagination::bootstrap-5') }}</div>
@endsection
