@extends('layouts.LayoutApp')
@section('title', 'Student Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Student report</h3>
    <div class="no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        <a href="{{ route('reports.students.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    </div>
</div>
@include('reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-4">
        <select name="class_id" class="form-select">
            <option value="">All classes</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button></div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Admission No</th><th>Name</th><th>Email</th><th>Class</th><th>Parent</th><th>Phone</th></tr></thead>
        <tbody>
        @forelse ($students as $s)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $s->admission_no }}</td>
                <td>{{ $s->user->name }}</td>
                <td>{{ $s->user->email }}</td>
                <td>{{ $s->class->full_name }}</td>
                <td>{{ $s->guardian?->user?->name ?? '-' }}</td>
                <td>{{ $s->phone ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No students found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<p class="mt-2 text-muted">Total students: <strong>{{ $students->count() }}</strong></p>
@endsection
