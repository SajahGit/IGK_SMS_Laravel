@extends('layouts.LayoutApp')
@section('title', 'Attendance Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Attendance report</h3>
    @if ($classId)
        <div class="no-print">
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.attendance.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
        </div>
    @endif
</div>
@include('reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
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
<p class="text-muted">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}.
    Late counts as attended. Below 75% is highlighted.</p>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Admission No</th><th>Name</th><th>Total days</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr class="{{ $r['summary']['total'] > 0 && $r['summary']['percentage'] < 75 ? 'table-danger' : '' }}">
                <td>{{ $r['student']->admission_no }}</td>
                <td>{{ $r['student']->user->name }}</td>
                <td>{{ $r['summary']['total'] }}</td>
                <td>{{ $r['summary']['present'] }}</td>
                <td>{{ $r['summary']['absent'] }}</td>
                <td>{{ $r['summary']['late'] }}</td>
                <td><strong>{{ $r['summary']['percentage'] }}%</strong></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No students in this class.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endif
@endsection
