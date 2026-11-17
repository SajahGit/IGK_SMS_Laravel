<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div style="min-width: 240px;">
        @include('Layouts.LayoutStat', ['icon' => 'trophy-fill', 'value' => $average !== null ? $average.'%' : '-', 'label' => 'Overall average', 'tone' => 'violet'])
    </div>
    @if ($results->isNotEmpty())
        <div class="input-group input-group-sm" style="max-width: 300px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="studentResultsFilterInput" class="form-control" placeholder="Search exam, subject, date...">
        </div>
    @endif
</div>

<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover mb-0" id="studentResultsTable">
        <thead class="table-light"><tr><th>Date</th><th>Exam</th><th>Subject</th><th>Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
        <tbody>
        @forelse ($results as $r)
            @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $r->exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
            <tr class="result-row-item">
                <td>{{ $r->exam->exam_date->format('d M Y') }}</td>
                <td><strong>{{ $r->exam->name }}</strong></td>
                <td>{{ $r->exam->subject->name }}</td>
                <td>{{ \App\Support\SchoolHelper::formatMarks($r->marks) }} / {{ $r->exam->max_marks }}</td>
                <td>{{ $pct }}%</td>
                <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }}">{{ $grade }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No results yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var filterInput = document.getElementById('studentResultsFilterInput');
        if (!filterInput) return;
        filterInput.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('#studentResultsTable tbody tr.result-row-item').forEach(function (row) {
                var text = row.innerText.toLowerCase();
                row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    });
</script>
