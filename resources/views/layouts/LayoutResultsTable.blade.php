<div class="mb-3" style="max-width: 300px;">
    @include('layouts.LayoutStat', ['icon' => 'trophy-fill', 'value' => $average !== null ? $average.'%' : '-', 'label' => 'Overall average', 'tone' => 'violet'])
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Date</th><th>Exam</th><th>Subject</th><th>Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
        <tbody>
        @forelse ($results as $r)
            @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $r->exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
            <tr>
                <td>{{ $r->exam->exam_date->format('d M Y') }}</td>
                <td>{{ $r->exam->name }}</td>
                <td>{{ $r->exam->subject->name }}</td>
                <td>{{ \App\Support\SchoolHelper::formatMarks($r->marks) }} / {{ $r->exam->max_marks }}</td>
                <td>{{ $pct }}%</td>
                <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }}">{{ $grade }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No results yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
