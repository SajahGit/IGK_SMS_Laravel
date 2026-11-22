<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transcript - {{ $student->admission_no }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: a4 portrait; margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        h1, h2, h3, p { margin: 0; }
        .title { text-align: center; margin-bottom: 12px; color: #1e3a8a; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 12px; }
        .meta td, .marks th, .marks td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
        .marks th { background: #f1f5f9; text-align: left; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .year { margin-top: 16px; color: #1e40af; font-size: 13px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .term-title { margin-top: 8px; margin-bottom: 4px; font-size: 11px; color: #334155; }
        .summary { text-align: right; font-weight: bold; margin: 8px 0 14px; font-size: 11px; color: #0f172a; }
    </style>
</head>
<body>
    <h1 class="title">Official Academic Transcript</h1>
    <table class="meta">
        <tr>
            <td style="width: 40%;">Student: <strong>{{ $student->user?->name }}</strong></td>
            <td style="width: 30%;">Admission: <strong>{{ $student->admission_no }}</strong></td>
            <td style="width: 30%;">Class: {{ $student->class?->full_name ?? '—' }}</td>
        </tr>
    </table>
    <p class="summary">Cumulative GPA: {{ $cgpa !== null ? number_format($cgpa, 2).' / '.number_format($gradePointMax, 2) : '—' }} &bull; Total Credits: {{ $totalCredits }}</p>
    @foreach($years as $year)
        <h2 class="year">Academic Year {{ $year['academic_year']?->name ?? 'Unknown' }} &mdash; GPA {{ $year['gpa'] !== null ? number_format($year['gpa'], 2) : '—' }}</h2>
        @foreach($year['terms'] as $term)
            <h3 class="term-title">{{ $term['term']->name }} &mdash; GPA {{ $term['gpa'] !== null ? number_format($term['gpa'], 2) : '—' }} ({{ ucfirst($term['term_result']->result_status) }})</h3>
            <table class="marks">
                <thead>
                    <tr>
                        <th style="width: 42%;">Subject</th>
                        <th style="width: 12%;" class="center">Credits</th>
                        <th style="width: 14%;" class="center">Percent</th>
                        <th style="width: 14%;" class="center">Grade</th>
                        <th style="width: 18%;" class="center">Grade Point</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($term['subjects'] as $subject)
                        <tr>
                            <td>{{ $subject->subject->name }}</td>
                            <td class="center">{{ $subject->credit_hours }}</td>
                            <td class="center">{{ $subject->percentage }}%</td>
                            <td class="center">{{ $subject->grade }}</td>
                            <td class="center">{{ number_format((float) $subject->grade_point, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="center" style="color: #64748b;">No graded subjects recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($term['exam_results']->isNotEmpty())
                <h3 class="term-title">Published exam results</h3>
                <table class="marks">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Exam</th>
                            <th style="width: 28%;">Subject</th>
                            <th style="width: 16%;" class="center">Marks</th>
                            <th style="width: 12%;" class="center">Percent</th>
                            <th style="width: 10%;" class="center">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($term['exam_results'] as $examResult)
                            <tr>
                                <td>{{ $examResult['exam']->name }}</td>
                                <td>{{ $examResult['subject']->name }}</td>
                                <td class="center">{{ $examResult['status'] ?? number_format($examResult['marks'], 2).' / '.$examResult['max_marks'] }}</td>
                                <td class="center">{{ $examResult['percentage'] === null ? '—' : number_format($examResult['percentage'], 2).'%' }}</td>
                                <td class="center">{{ $examResult['grade'] ?? $examResult['status'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endforeach
</body>
</html>
