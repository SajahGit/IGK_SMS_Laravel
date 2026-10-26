@extends('layouts.LayoutApp')
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
        <thead><tr><th>Date</th><th>Present</th><th>Absent</th><th>Late</th><th class="text-end"></th></tr></thead>
        <tbody>
        @forelse ($byDate as $d => $counts)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($d)->format('d M Y (D)') }}</td>
                <td>{{ $counts['present'] ?? 0 }}</td>
                <td>{{ $counts['absent'] ?? 0 }}</td>
                <td>{{ $counts['late'] ?? 0 }}</td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('teacher.attendance.create', ['class_id' => $classId, 'date' => substr($d, 0, 10)]) }}">View / edit</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No attendance recorded in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endif
@endsection
