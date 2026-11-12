@extends('Layouts.LayoutApp')
@section('title', 'Student Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Student report</h3>
    <div class="no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        <a href="{{ route('reports.pdf', ['report' => 'students'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.students.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    </div>
</div>
@include('Reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-2">
        <select name="class_id" class="form-select">
            <option value="">All classes</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? null) == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="program_id" class="form-select">
            <option value="">All programs</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="academic_year_id" class="form-select">
            <option value="">All years</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? null) == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="gender" class="form-select">
            <option value="">All genders</option>
            @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['gender'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (['active', 'suspended', 'graduated', 'withdrawn'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Apply</button></div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Admission No</th><th>Name</th><th>Program</th><th>Section</th><th>Status</th><th>Admission Date</th><th>Guardian Phone</th></tr></thead>
        <tbody>
        @forelse ($students as $s)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $s->admission_no }}</td>
                <td>{{ $s->user->name }}</td>
                <td>{{ $s->program?->name ?? $s->class->program?->name ?? '—' }}</td>
                <td>{{ $s->class?->full_name ?? 'Unassigned' }}</td>
                <td>{{ ucfirst($s->status) }}</td>
                <td>{{ $s->admission_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $s->primaryGuardian->first()?->phone ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<p class="mt-2 text-muted">Total students: <strong>{{ $students->count() }}</strong></p>
@endsection
