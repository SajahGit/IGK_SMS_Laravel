@extends('Layouts.LayoutApp')
@section('title', 'Daily Attendance Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Daily attendance register</h3>
        <small class="text-muted">R3 · Session-level student status, remarks, recorder and time.</small>
    </div>
    @if ($classId)
        <div class="no-print">
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.attendance.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
        </div>
    @endif
</div>
@include('Reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-2">
        <select name="class_id" class="form-select" required>
            <option value="">Select class/section</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="subject_id" class="form-select">
            <option value="">All subjects</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected($subjectId == $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="teacher_id" class="form-select">
            <option value="">All teachers</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected($teacherId == $teacher->id)>{{ $teacher->user?->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control" aria-label="From date"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control" aria-label="To date"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button></div>
</form>

@if ($classId)
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Date</th><th>Class/Section</th><th>Subject</th><th>Teacher</th><th>Admission No</th><th>Student</th><th>Status</th><th>Remarks</th><th>Taken by</th><th>Time</th></tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['session']->date->format('d M Y') }}</td>
                <td>{{ $row['session']->schoolClass->full_name }}</td>
                <td>{{ $row['session']->classSubject?->subject?->name ?? $row['session']->session_label }}</td>
                <td>{{ $row['session']->classSubject?->teacher?->user?->name ?? '—' }}</td>
                <td>{{ $row['record']->student->admission_no }}</td>
                <td>{{ $row['record']->student->user->name }}</td>
                <td>{{ ucfirst($row['record']->status) }}</td>
                <td>{{ $row['record']->remarks ?: '—' }}</td>
                <td>{{ $row['session']->taker?->name ?? '—' }}</td>
                <td>{{ $row['session']->start_time ? substr($row['session']->start_time, 0, 5) : ($row['session']->created_at?->format('H:i') ?? '—') }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="text-center text-muted">No attendance records match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endif
@endsection
