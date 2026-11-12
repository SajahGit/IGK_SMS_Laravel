@extends('Layouts.LayoutApp')
@section('title', 'My Hostel')
@section('content')
<h3 class="mb-3">My Hostel Assignment</h3>
<div class="row g-3">
    @forelse ($assignments as $assignment)
        <div class="col-lg-6">
            <section class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="h5">{{ $assignment->room->hostel->name }}</h4>
                    <p class="mb-1">Room {{ $assignment->room->room_number }}, bed {{ $assignment->bed_number }}</p>
                    <p class="text-muted mb-1">{{ $assignment->room->hostel->location ?: 'Location not recorded' }}</p>
                    <p class="small text-muted mb-0">{{ $assignment->academicYear->name }} · {{ $assignment->start_date->format('d M Y') }} – {{ $assignment->end_date?->format('d M Y') ?? 'Ongoing' }}</p>
                </div>
            </section>
        </div>
    @empty
        <div class="col-12"><div class="card shadow-sm"><div class="card-body text-muted">No current hostel assignment is recorded.</div></div></div>
    @endforelse
</div>
@endsection
