@extends('Layouts.LayoutApp')
@section('title', 'Class Result Summary')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Class result summary</h3>
        <small class="text-muted">R7 · Published term grades, weighted GPA, pass/fail and section rank.</small>
    </div>
    @if ($termId)
        <div class="no-print">
            <a href="{{ route('reports.class-result.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
        </div>
    @endif
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-3">
        <label class="form-label">Term</label>
        <select name="term_id" class="form-select" required>
            <option value="">Select term</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Class/Section</label>
        <select name="class_id" class="form-select">
            <option value="">All sections</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
            <option value="">All programs</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected($programId == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
    </div>
</form>

@if ($stats)
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Students</div><div class="h5">{{ $stats['count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Published Results</div><div class="h5">{{ $stats['graded'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Passed</div><div class="h5">{{ $stats['passed'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="text-muted small">Average GPA</div><div class="h5">{{ $stats['average_gpa'] ?? '—' }}</div></div></div></div>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Rank in section</th><th>Class/Section</th><th>Admission No</th><th>Student</th>
                @foreach ($subjects as $subject)<th>{{ $subject->name }}</th>@endforeach
                <th>GPA</th><th>Pass/Fail</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['rank'] ?? '—' }}</td>
                    <td>{{ $row['student']->class->full_name }}</td>
                    <td>{{ $row['student']->admission_no }}</td>
                    <td>{{ $row['student']->user->name }}</td>
                    @foreach ($subjects as $subject)
                        <td>{{ $row['subjects'][$subject->id]['grade'] ?? '—' }}
                            @if (isset($row['subjects'][$subject->id]['percentage']))
                                <small class="text-muted">({{ $row['subjects'][$subject->id]['percentage'] }}%)</small>
                            @endif
                        </td>
                    @endforeach
                    <td>{{ $row['gpa'] ?? '—' }}</td>
                    <td>{{ $row['pass'] ? 'Pass' : 'Fail' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 6 + $subjects->count() }}" class="text-center text-muted py-4">No published results found. Select a term with published exam results.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
