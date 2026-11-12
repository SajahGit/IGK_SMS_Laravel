@extends('Layouts.LayoutApp')
@section('title', 'Enrollment Register')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Enrollment Register</h3>
    <div class="no-print">
        <a href="{{ route('reports.pdf', ['report' => 'enrollment'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.enrollment.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    </div>
</div>

@include('Reports.ReportClassFilterNote')

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-2">
        <label class="form-label visually-hidden">Class</label>
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
        <select name="term_id" class="form-select">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected(($filters['term_id'] ?? null) == $term->id)>{{ $term->academicYear->name }} — {{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label visually-hidden">Subject</label>
        <select name="subject_id" class="form-select">
            <option value="">All subjects</option>
            @foreach ($subjects as $s)
                <option value="{{ $s->id }}" @selected(($filters['subject_id'] ?? null) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All enrollment statuses</option>
            @foreach (['enrolled', 'dropped', 'completed'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Term</th><th>Program</th><th>Section</th><th>Subject</th><th>Teacher</th><th>Student Count</th><th>Student List</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['term'] }}</td>
                    <td>{{ $row['program'] }}</td>
                    <td>{{ $row['class'] }}</td>
                    <td>{{ $row['subject'] }}</td>
                    <td>{{ $row['teacher'] }}</td>
                    <td>{{ $row['student_count'] }}</td>
                    <td>{{ $row['student_list'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No enrollment data matches these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
