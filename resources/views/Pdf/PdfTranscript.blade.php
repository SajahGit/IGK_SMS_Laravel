<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transcript - {{ $student->admission_no }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1, h2, h3, p { margin: 0; }
        .title { text-align: center; margin-bottom: 16px; }
        .meta, .marks { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td, .marks th, .marks td { border: 1px solid #cbd4e1; padding: 6px; }
        .marks th { background: #edf2f8; text-align: left; }
        .year { margin-top: 16px; color: #244c86; }
        .summary { text-align: right; font-weight: bold; margin: 8px 0; }
    </style>
</head>
<body>
    <h1 class="title">Academic Transcript</h1>
    <table class="meta"><tr><td>Student: <strong>{{ $student->user?->name }}</strong></td><td>Admission: <strong>{{ $student->admission_no }}</strong></td><td>Class: {{ $student->class?->full_name ?? '—' }}</td></tr></table>
    <p class="summary">Cumulative GPA: {{ $cgpa !== null ? number_format($cgpa, 2).' / 4.00' : '—' }} · Total credits: {{ $totalCredits }}</p>
    @foreach($years as $year)
        <h2 class="year">Academic year {{ $year['academic_year']?->name ?? 'Unknown' }} — GPA {{ $year['gpa'] !== null ? number_format($year['gpa'], 2) : '—' }}</h2>
        @foreach($year['terms'] as $term)
            <h3>{{ $term['term']->name }} — GPA {{ $term['gpa'] !== null ? number_format($term['gpa'], 2) : '—' }} ({{ ucfirst($term['term_result']->result_status) }})</h3>
            <table class="marks"><thead><tr><th>Subject</th><th>Credits</th><th>Percent</th><th>Grade</th><th>Grade point</th></tr></thead>
                <tbody>@forelse($term['subjects'] as $subject)
                    <tr><td>{{ $subject->subject->name }}</td><td>{{ $subject->credit_hours }}</td><td>{{ $subject->percentage }}%</td><td>{{ $subject->grade }}</td><td>{{ number_format((float) $subject->grade_point, 2) }}</td></tr>
                @empty<tr><td colspan="5">No graded subjects.</td></tr>@endforelse</tbody>
            </table>
        @endforeach
    @endforeach
</body>
</html>
