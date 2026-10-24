@extends('layouts.LayoutApp')
@section('title', 'Exam Results')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $exam->name }}</h3>
    <div>
        <a href="{{ route('teacher.marks.edit', $exam) }}" class="btn btn-primary">Edit marks</a>
        <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
<p class="text-muted">{{ $exam->class->full_name }} &middot; {{ $exam->subject->name }} &middot; Maximum marks: {{ $exam->max_marks }}</p>

<div class="row g-3 mb-4">
    @foreach ([['Entered', $stats['count'], 'pencil-square', 'indigo'], ['Average', $stats['average'], 'bar-chart-fill', 'cyan'],
               ['Highest', $stats['highest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['highest']) : '-', 'arrow-up-circle-fill', 'green'],
               ['Lowest', $stats['lowest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['lowest']) : '-', 'arrow-down-circle-fill', 'rose'],
               ['Passed (40%+)', $stats['passed'], 'patch-check-fill', 'amber']] as [$label, $value, $icon, $tone])
        <div class="col-6 col-xl">@include('layouts.LayoutStat', ['icon' => $icon, 'value' => $value, 'label' => $label, 'tone' => $tone])</div>
    @endforeach
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Admission No</th><th>Name</th><th>Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
        <tbody>
        @forelse ($results as $r)
            @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
            <tr>
                <td>{{ $r->student->admission_no }}</td>
                <td>{{ $r->student->user->name }}</td>
                <td>{{ \App\Support\SchoolHelper::formatMarks($r->marks) }}</td>
                <td>{{ $pct }}%</td>
                <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }}">{{ $grade }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No marks entered yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
