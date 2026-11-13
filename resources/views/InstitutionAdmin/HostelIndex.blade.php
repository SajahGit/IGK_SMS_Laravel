@extends('Layouts.LayoutApp')
@section('title', 'Hostel Management')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Hostel Management</h3>
        <small class="text-muted">Manage hostels, room capacity, and dated student bed assignments.</small>
    </div>
    <a href="{{ route('admin.hostels.assignments.create') }}" class="btn btn-success">Assign Student</a>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Add Hostel</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hostels.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4"><label class="form-label" for="hostel-name">Name</label><input id="hostel-name" name="name" class="form-control" pattern="[a-zA-Z0-9\s'-]+" maxlength="80" required></div>
            <div class="col-md-5"><label class="form-label" for="hostel-location">Location</label><input id="hostel-location" name="location" class="form-control" pattern="[a-zA-Z0-9\s,.-]+" maxlength="120"></div>
            <div class="col-md-3"><button class="btn btn-primary">Add Hostel</button></div>
        </form>
    </div>
</div>

@forelse ($hostels as $hostel)
    <section class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">{{ $hostel->name }}</h5>
                    <span class="small text-muted">{{ $hostel->location ?: 'Location not recorded' }}</span>
                    <span class="badge {{ $hostel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $hostel->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <form method="POST" action="{{ route('admin.hostels.update', $hostel) }}" class="d-flex flex-wrap gap-2 align-items-start">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $hostel->name }}" class="form-control form-control-sm" aria-label="Hostel name" pattern="[a-zA-Z0-9\s'-]+" maxlength="80" required>
                    <input name="location" value="{{ $hostel->location }}" class="form-control form-control-sm" aria-label="Hostel location" pattern="[a-zA-Z0-9\s,.-]+" maxlength="120">
                    <select name="is_active" class="form-select form-select-sm" aria-label="Hostel status">
                        <option value="1" @selected($hostel->is_active)>Active</option>
                        <option value="0" @selected(! $hostel->is_active)>Inactive</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Update Status</button>
                </form>
                <form method="POST" action="{{ route('admin.hostels.destroy', $hostel) }}" onsubmit="return confirm('Delete this empty hostel?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete empty hostel</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if ($hostel->is_active)
                <form method="POST" action="{{ route('admin.hostels.rooms.store', $hostel) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-4"><label class="form-label" for="room-number-{{ $hostel->id }}">Room number</label><input id="room-number-{{ $hostel->id }}" name="room_number" data-uppercase-filter="code" pattern="[A-Z0-9-]+" class="form-control" maxlength="15" required></div>
                    <div class="col-md-3"><label class="form-label" for="room-capacity-{{ $hostel->id }}">Bed capacity</label><input id="room-capacity-{{ $hostel->id }}" name="bed_capacity" type="number" min="1" max="100" class="form-control" required></div>
                    <div class="col-md-3"><button class="btn btn-outline-primary">Add Room</button></div>
                </form>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Room</th><th>Capacity</th><th>Current assignments</th><th>Status</th><th>Update room</th></tr></thead>
                    <tbody>
                    @forelse ($hostel->rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->bed_capacity }}</td>
                            <td>{{ $room->active_assignments }}</td>
                            <td><span class="badge {{ $room->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $room->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.hostels.rooms.update', $room) }}" class="d-flex flex-wrap gap-2">
                                    @csrf @method('PUT')
                                    <input name="room_number" value="{{ $room->room_number }}" data-uppercase-filter="code" pattern="[A-Z0-9-]+" class="form-control form-control-sm" aria-label="Room number" maxlength="15" required>
                                    <input name="bed_capacity" value="{{ $room->bed_capacity }}" type="number" min="1" max="100" class="form-control form-control-sm" aria-label="Bed capacity" required>
                                    <select name="is_active" class="form-select form-select-sm" aria-label="Room status">
                                        <option value="1" @selected($room->is_active)>Active</option>
                                        <option value="0" @selected(! $room->is_active)>Inactive</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.hostels.rooms.destroy', $room) }}" class="mt-1" onsubmit="return confirm('Delete this room if it has no assignment history?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete empty room</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No rooms have been added to this hostel.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@empty
    <div class="card shadow-sm border-0 mb-4"><div class="card-body text-muted">No hostels have been added.</div></div>
@endforelse

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent"><h5 class="mb-0">Student Assignments</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Hostel / Room / Bed</th><th>Academic year</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->student?->user?->name ?? $assignment->student?->admission_no ?? 'Unknown student' }}<div class="small text-muted">{{ $assignment->student?->admission_no ?? '—' }}</div></td>
                    <td>{{ $assignment->room?->hostel?->name ?? 'Unknown hostel' }}<div class="small text-muted">Room {{ $assignment->room?->room_number ?? '—' }}, bed {{ $assignment->bed_number }}</div></td>
                    <td>{{ $assignment->academicYear?->name ?? '—' }}</td>
                    <td>{{ $assignment->start_date->format('d M Y') }} – {{ $assignment->end_date?->format('d M Y') ?? 'Ongoing' }}</td>
                    <td><span class="badge {{ $assignment->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($assignment->status) }}</span></td>
                    <td>
                        <a href="{{ route('admin.hostels.assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @if ($assignment->status === 'active')
                            <form method="POST" action="{{ route('admin.hostels.assignments.end', $assignment) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">End Assignment</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No student hostel assignments have been recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $assignments->links('pagination::bootstrap-5') }}</div>
@endsection
