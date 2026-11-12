@extends('Layouts.LayoutApp')
@section('title', 'Class Timetable')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">Class Timetable ({{ $student->class?->full_name ?? 'Unassigned' }})</h3>
        <small class="text-muted">Weekly subject schedule, class timings, and classroom rooms.</small>
    </div>
    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Timetable
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
                            <div class="fw-bold text-primary">{{ $slot->classSubject->subject->name }}</div>
                            <div class="small text-muted"><i class="bi bi-person me-1"></i>{{ $slot->classSubject->teacher->user->name }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small">
                                <span class="badge bg-dark-subtle text-body border"><i class="bi bi-clock me-1"></i>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-door-closed me-1"></i>{{ $slot->room }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-calendar-minus d-block fs-4 mb-1"></i>
                            No sessions
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
