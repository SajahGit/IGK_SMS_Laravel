@extends('Layouts.LayoutApp')
@section('title', 'Result Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Exam results report</h3>
    @if ($exam)
        <div class="no-print">
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('reports.pdf', ['report' => 'results'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
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
                    {{ $e->name }} - {{ $e->class?->full_name ?? 'Unassigned' }} - {{ $e->subject->name }} ({{ $e->exam_date->format('d M Y') }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button></div>
</form>

@if ($exams->isEmpty() && !$exam && !request()->attributes->has('pdf_export_limit'))
    <div class="alert alert-info">No exams available yet.</div>
@endif

@if ($exam)
    <p class="text-muted">R6 · {{ $exam->name }} &middot; {{ $exam->class?->full_name ?? 'Unassigned' }} &middot; {{ $exam->subject->name }} &middot; Maximum marks: {{ $exam->max_marks }}</p>

    <div class="row g-3 mb-3">
        @foreach ([['Students', $stats['count'], 'people-fill', 'indigo'], ['Average', $stats['average'], 'bar-chart-fill', 'cyan'],
                   ['Highest', $stats['highest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['highest']) : '-', 'arrow-up-circle-fill', 'green'],
                   ['Lowest', $stats['lowest'] !== null ? \App\Support\SchoolHelper::formatMarks($stats['lowest']) : '-', 'arrow-down-circle-fill', 'rose'],
                   ['Passed (40%+)', $stats['passed'], 'patch-check-fill', 'amber']] as [$label, $value, $icon, $tone])
            <div class="col-6 col-xl">@include('Layouts.LayoutStat', ['icon' => $icon, 'value' => $value, 'label' => $label, 'tone' => $tone])</div>
        @endforeach
    </div>

    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Rank</th><th class="print-nowrap">Admission No</th><th>Name</th><th class="print-nowrap">Marks</th><th class="print-nowrap">Maximum</th><th class="print-nowrap">Percentage</th><th>Grade</th><th>Absent</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r['rank'] }}</td>
                    <td class="print-nowrap">{{ $r['result']->student->admission_no }}</td>
                    <td>{{ $r['result']->student->user->name }}</td>
                    <td>{{ $r['absent'] ? 'Absent' : \App\Support\SchoolHelper::formatMarks($r['result']->marks) }}</td>
                    <td>{{ $exam->max_marks }}</td>
                    <td>{{ $r['percentage'] }}%</td>
                    <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($r['grade']) }}">{{ $r['grade'] }}</span></td>
                    <td>{{ $r['absent'] ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No marks entered for this exam.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
@endif
@endsection
