@extends('layouts.LayoutApp')
@section('title', 'Result Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Result report</h3>
    @if ($exam)
        <div class="no-print">
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.results.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
        </div>
    @endif
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-6">
        <select name="exam_id" class="form-select" required>
            <option value="">Select exam</option>
            @foreach ($exams as $e)
                <option value="{{ $e->id }}" @selected($exam && $exam->id == $e->id)>
                    {{ $e->name }} - {{ $e->class->full_name }} - {{ $e->subject->name }} ({{ $e->exam_date->format('d M Y') }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button></div>
</form>

@if ($exams->isEmpty())
    <div class="alert alert-info">No exams available yet.</div>
@endif

@if ($exam)
    <p class="text-muted">{{ $exam->name }} &middot; {{ $exam->class->full_name }} &middot; {{ $exam->subject->name }} &middot; Maximum marks: {{ $exam->max_marks }}</p>

    <div class="row g-3 mb-3">
        @foreach ([['Students', $stats['count'], 'people-fill', 'indigo'], ['Average', $stats['average'], 'bar-chart-fill', 'cyan'],
                   ['Highest', $stats['highest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['highest']) : '-', 'arrow-up-circle-fill', 'green'],
                   ['Lowest', $stats['lowest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['lowest']) : '-', 'arrow-down-circle-fill', 'rose'],
                   ['Passed (40%+)', $stats['passed'], 'patch-check-fill', 'amber']] as [$label, $value, $icon, $tone])
            <div class="col-6 col-xl">@include('layouts.LayoutStat', ['icon' => $icon, 'value' => $value, 'label' => $label, 'tone' => $tone])</div>
        @endforeach
    </div>

    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Rank</th><th>Admission No</th><th>Name</th><th>Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r['rank'] }}</td>
                    <td>{{ $r['result']->student->admission_no }}</td>
                    <td>{{ $r['result']->student->user->name }}</td>
                    <td>{{ \App\Support\SchoolHelper::formatMarks($r['result']->marks) }}</td>
                    <td>{{ $r['percentage'] }}%</td>
                    <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($r['grade']) }}">{{ $r['grade'] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No marks entered for this exam.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
@endif
@endsection
