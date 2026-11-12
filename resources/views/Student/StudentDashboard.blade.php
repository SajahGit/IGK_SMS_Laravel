@extends('Layouts.LayoutApp')
@section('title', 'My Dashboard')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
    <h3 class="mb-0">Welcome, {{ $student->user->name }}</h3>
    <a href="{{ route('student.electives.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-journal-plus me-1"></i>Electives</a>
</div>
<p class="text-muted mb-4">{{ $student->class?->full_name ?? 'Unassigned' }} &middot; Admission no {{ $student->admission_no }}</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">@include('Layouts.LayoutStat', ['icon' => 'calendar2-check-fill', 'value' => $summary['percentage'].'%', 'label' => 'Attendance', 'tone' => 'green'])</div>
    <div class="col-6 col-xl-3">@include('Layouts.LayoutStat', ['icon' => 'check2-circle', 'value' => ($summary['present'] + $summary['late']).'/'.$summary['total'], 'label' => 'Days attended', 'tone' => 'cyan'])</div>
    <div class="col-6 col-xl-3">@include('Layouts.LayoutStat', ['icon' => 'x-circle-fill', 'value' => $summary['absent'], 'label' => 'Days absent', 'tone' => 'rose'])</div>
    <div class="col-6 col-xl-3">@include('Layouts.LayoutStat', ['icon' => 'trophy-fill', 'value' => $average !== null ? $average.'%' : '-', 'label' => 'Average result', 'tone' => 'violet'])</div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between"><span>School transport</span><span class="text-muted small">{{ $transportAssignment?->academicYear?->name ?? 'Current academic year' }}</span></div>
    <div class="card-body">
        @if ($transportAssignment)
            <div class="row g-3">
                <div class="col-md-3"><div class="text-muted small">Route</div><strong>{{ $transportAssignment->route->name }}</strong></div>
                <div class="col-md-3"><div class="text-muted small">Pickup stop</div><strong>{{ $transportAssignment->stop->name }}</strong></div>
                <div class="col-md-3"><div class="text-muted small">Pickup / drop-off</div><strong>{{ $transportAssignment->stop->pickup_time ? substr($transportAssignment->stop->pickup_time, 0, 5) : '—' }} / {{ $transportAssignment->stop->dropoff_time ? substr($transportAssignment->stop->dropoff_time, 0, 5) : '—' }}</strong></div>
                <div class="col-md-3"><div class="text-muted small">Vehicle / driver</div><strong>{{ $transportAssignment->route->vehicle?->registration_number ?? 'Pending' }} · {{ $transportAssignment->route->vehicle?->driver_name ?? 'Not assigned' }}</strong></div>
            </div>
        @else
            <p class="text-muted mb-0">No active transport assignment is recorded for the current academic year.</p>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between"><span>Latest results</span><a href="{{ route('student.results') }}" class="small">All results</a></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead>
            <tbody>
            @forelse ($latestResults as $r)
                @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $r->exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
                <tr>
                    <td>{{ $r->exam->name }}</td>
                    <td>{{ $r->exam->subject->name }}</td>
                    <td>{{ \App\Support\SchoolHelper::formatMarks($r->marks) }} / {{ $r->exam->max_marks }}</td>
                    <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }}">{{ $grade }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No results yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
