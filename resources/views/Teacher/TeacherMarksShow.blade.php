@extends('Layouts.LayoutApp')
@section('title', 'Exam Results')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $exam->name }}</h3>
    <div>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Back</a>
        @else
            <a href="{{ route('teacher.marks.edit', $exam) }}" class="btn btn-primary">Edit marks</a>
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Back</a>
        @endif
    </div>
</div>
<p class="text-muted">{{ $exam->class?->full_name ?? 'Unassigned' }} &middot; {{ $exam->subject?->name ?? 'Unassigned' }} &middot; Maximum marks: {{ $exam->max_marks }}</p>

<div class="row g-3 mb-4">
    @foreach ([['Entered', $stats['count'], 'pencil-square', 'indigo'], ['Average', $stats['average'], 'bar-chart-fill', 'cyan'],
               ['Highest', $stats['highest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['highest']) : '-', 'arrow-up-circle-fill', 'green'],
               ['Lowest', $stats['lowest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['lowest']) : '-', 'arrow-down-circle-fill', 'rose'],
               ['Passed (40%+)', $stats['passed'], 'patch-check-fill', 'amber']] as [$label, $value, $icon, $tone])
        <div class="col-6 col-xl">@include('Layouts.LayoutStat', ['icon' => $icon, 'value' => $value, 'label' => $label, 'tone' => $tone])</div>
    @endforeach
</div>

<div class="card shadow-sm">
    <div class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center">
        <span class="small fw-semibold text-muted">Results by Student</span>
        <div class="input-group input-group-sm" style="max-width: 280px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="resultFilterInput" class="form-control" placeholder="Search student or admission no...">
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-hover mb-0" id="resultsTable">
        <thead><tr><th>Admission No</th><th>Name</th><th>Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
        <tbody>
        @forelse ($results as $r)
            @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
            <tr class="result-row">
                <td><code>{{ $r->student?->admission_no ?? '-' }}</code></td>
                <td class="student-name"><strong>{{ $r->student?->user?->name ?? '-' }}</strong></td>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var filterInput = document.getElementById('resultFilterInput');
        if (!filterInput) return;
        filterInput.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('#resultsTable tbody tr.result-row').forEach(function (row) {
                var text = row.innerText.toLowerCase();
                row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    });
</script>
@endsection
