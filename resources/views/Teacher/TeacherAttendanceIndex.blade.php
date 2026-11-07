@extends('Layouts.LayoutApp')
@section('title', 'Attendance History')
@section('content')
<h3 class="mb-3">Attendance history</h3>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="class_id" class="form-select" required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button></div>
</form>

@if ($classId)
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Date</th><th>Session</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Taken by</th><th class="text-end"></th></tr></thead>
        <tbody>
        @forelse ($byDate as $session)
            <tr>
                <td>{{ $session->date->format('d M Y (D)') }}</td>
                <td>
                    {{ $session->classSubject?->subject?->name ?? $session->session_label }}
                    @if ($session->start_time) · {{ substr($session->start_time, 0, 5) }} @endif
                </td>
                <td>{{ $session->counts['present'] ?? 0 }}</td>
                <td>{{ $session->counts['absent'] ?? 0 }}</td>
                <td>{{ $session->counts['late'] ?? 0 }}</td>
                <td>{{ $session->counts['excused'] ?? 0 }}</td>
                <td>{{ $session->taker?->name ?? '—' }}</td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route($attendanceRoutePrefix.'.attendance.create', ['session_id' => $session->id]) }}">View / edit</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">No attendance sessions recorded in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endif
@endsection
