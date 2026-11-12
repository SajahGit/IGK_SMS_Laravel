@extends('Layouts.LayoutApp')
@section('title', 'Elective Registration')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-1">Elective Registration</h3>
        <p class="text-muted mb-0">
            {{ $term ? $term->academicYear->name.' · '.$term->name : 'No current academic term' }}
            @if ($term)
                · Add/drop {{ $term->start_date->format('M j, Y') }} to {{ $term->end_date->format('M j, Y') }}
            @endif
        </p>
    </div>
    <a href="{{ route('student.timetable') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calendar-week me-1"></i>View timetable
    </a>
</div>

@if (! $registrationOpen)
    <div class="alert alert-info" role="status">Elective registration is closed for the current term.</div>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Subject</th><th>Teacher</th><th>Schedule</th><th>Seats</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse ($offerings as $offering)
                @php
                    $status = $enrollmentStatuses[$offering->id] ?? null;
                    $enrolled = (int) ($enrollmentCounts[$offering->id] ?? 0);
                    $capacity = (int) $offering->schoolClass->capacity;
                    $full = $enrolled >= $capacity;
                @endphp
                <tr>
                    <td><strong>{{ $offering->subject->name }}</strong><div class="small text-muted">{{ $offering->subject->code }}</div></td>
                    <td>{{ $offering->teacher?->user?->name ?? 'Not assigned' }}</td>
                    <td>
                        @forelse ($offering->timetableSlots as $slot)
                            <div class="small">{{ $slot->day_name }} {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</div>
                        @empty
                            <span class="text-muted small">Not scheduled</span>
                        @endforelse
                    </td>
                    <td>{{ $enrolled }} / {{ $capacity }}</td>
                    <td>
                        @if ($status === 'enrolled')
                            <span class="badge text-bg-success">Enrolled</span>
                        @elseif ($status === 'dropped')
                            <span class="badge text-bg-secondary">Dropped</span>
                        @else
                            <span class="badge text-bg-light">Available</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if ($status === 'enrolled')
                            <form method="POST" action="{{ route('student.electives.drop', $offering) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger" @disabled(! $registrationOpen)>Drop</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('student.electives.register', $offering) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary" @disabled(! $registrationOpen || $full)>Register</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No electives are offered for your current program and term.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection