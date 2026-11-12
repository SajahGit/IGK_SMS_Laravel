@extends('Layouts.LayoutApp')
@section('title', 'Subject Performance Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Subject Academic Performance Report</h3>
        <small class="text-muted">R8 · Weighted subject results, pass rate and grade distribution.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.pdf', ['report' => 'subject-performance'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.subject-performance.csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download CSV
        </a>
    </div>
</div>

<form method="GET" action="{{ route('reports.subject-performance') }}" class="row g-2 mb-3 no-print">
    <div class="col-md-3">
        <label for="term_id" class="form-label">Term</label>
        <select name="term_id" id="term_id" class="form-select">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="class_id" class="form-label">Class/Section</label>
        <select name="class_id" id="class_id" class="form-select">
            <option value="">All sections</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="program_id" class="form-label">Program</label>
        <select name="program_id" id="program_id" class="form-select">
            <option value="">All programs</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected($programId == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="subject_id" class="form-label">Subject</label>
        <select name="subject_id" id="subject_id" class="form-select">
            <option value="">All subjects</option>
            @foreach ($subjectOptions as $subject)
                <option value="{{ $subject->id }}" @selected($subjectId == $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Apply filters</button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Subject</th>
                    <th class="text-center">Credit Hours</th>
                    <th class="text-center">Total Exams</th>
                    <th class="text-center">Students</th>
                    <th class="text-center">Average (%)</th>
                    <th class="text-center">Pass Rate</th>
                    <th class="text-center">Highest (%)</th>
                    <th class="text-center">Lowest (%)</th>
                    <th class="text-center">Grade Distribution ({{ implode('/', $gradeLabels) }})</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subjects as $s)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $s['subject']->name }}</div>
                            <small class="text-muted"><code>{{ $s['subject']->code }}</code></small>
                        </td>
                        <td class="text-center">{{ $s['subject']->credit_hours }}</td>
                        <td class="text-center">{{ $s['exams_count'] }}</td>
                        <td class="text-center">{{ $s['results_count'] }}</td>
                        <td class="text-center fw-bold text-primary">{{ $s['avg_percentage'] }}%</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $s['pass_rate'] >= 70 ? 'success' : ($s['pass_rate'] >= 40 ? 'warning' : 'danger') }} fs-6">
                                {{ $s['pass_rate'] }}%
                            </span>
                        </td>
                        <td class="text-center text-success fw-bold">{{ $s['highest_percentage'] }}</td>
                        <td class="text-center text-danger fw-bold">{{ $s['lowest_percentage'] }}</td>
                        <td class="text-center">{{ implode('/', array_map(fn ($grade) => $s['grades'][$grade] ?? 0, $gradeLabels)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No subjects evaluated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
