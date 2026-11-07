@extends('Layouts.LayoutApp')
@section('title', 'My Teaching Timetable')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">My Teaching Timetable</h3>
        <small class="text-muted">Weekly schedule of all classes, assigned subjects, and teaching rooms.</small>
    </div>
    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Schedule
    </button>
</div>

@php
    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];
@endphp

<div class="row g-3">
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
                        <div class="p-2 rounded border bg-body-tertiary">
                            <div class="fw-bold text-primary">{{ $slot->classSubject->schoolClass->full_name }}</div>
                            <div class="small fw-semibold text-body mb-1">{{ $slot->classSubject->subject->name }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small">
                                <span class="badge bg-dark-subtle text-body border"><i class="bi bi-clock me-1"></i>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-door-closed me-1"></i>{{ $slot->room }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-cup-hot d-block fs-4 mb-1"></i>
                            No classes
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
