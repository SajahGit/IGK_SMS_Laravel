@extends('Layouts.LayoutApp')
@section('title', 'Academic transcript')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Academic transcript</h3><small class="text-muted">{{ $student->user?->name }} · {{ $student->admission_no }}</small></div>
    @if(auth()->user()->isStudent())
        <a class="btn btn-outline-primary" href="{{ route('student.transcript.pdf') }}">Download PDF</a>
    @else
        <a class="btn btn-outline-primary" href="{{ route('guardian.children.transcript.pdf', $student) }}">Download PDF</a>
    @endif
</div>
<div class="card shadow-sm border-0 mb-3"><div class="card-body d-flex flex-wrap gap-4">
    <div><div class="small text-muted">Cumulative GPA (CGPA)</div><strong class="fs-4">{{ $cgpa !== null ? number_format($cgpa, 2).' / 4.00' : '—' }}</strong></div>
    <div><div class="small text-muted">Total credits</div><strong class="fs-4">{{ $totalCredits }}</strong></div>
    <div><div class="small text-muted">Current class</div><strong class="fs-5">{{ $student->class?->full_name ?? '—' }}</strong></div>
</div></div>
@forelse($years as $year)
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-transparent d-flex justify-content-between"><strong>Academic year {{ $year['academic_year']?->name ?? 'Unknown' }}</strong><span>Year GPA: {{ $year['gpa'] !== null ? number_format($year['gpa'], 2) : '—' }} · {{ $year['credits'] }} credits</span></div>
        <div class="card-body">
            @foreach($year['terms'] as $term)
                <h6 class="mt-2">{{ $term['term']->name }} · GPA {{ $term['gpa'] !== null ? number_format($term['gpa'], 2) : '—' }} · {{ ucfirst($term['term_result']->result_status) }}</h6>
                <div class="table-responsive"><table class="table table-sm table-hover">
                    <thead><tr><th>Subject</th><th>Credits</th><th>Percent</th><th>Grade</th><th>Grade point</th></tr></thead>
                    <tbody>@forelse($term['subjects'] as $subject)
                        <tr><td>{{ $subject->subject->name }}</td><td>{{ $subject->credit_hours }}</td><td>{{ $subject->percentage }}%</td><td>{{ $subject->grade }}</td><td>{{ number_format((float) $subject->grade_point, 2) }}</td></tr>
                    @empty<tr><td colspan="5" class="text-muted">No graded subjects were recorded.</td></tr>@endforelse</tbody>
                </table></div>
            @endforeach
        </div>
    </div>
@empty
    <div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-4">No published academic-year results are available yet.</div></div>
@endforelse
@endsection
