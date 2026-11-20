<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $student->admission_no }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: a4 portrait; margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 14px; }
        .header h1 { color: #1e3a8a; font-size: 19px; margin-bottom: 4px; }
        .header p { color: #475569; font-size: 9.5px; margin-top: 2px; }
        .title { text-align: center; margin: 10px 0 3px; font-size: 14px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta, .summary { margin-bottom: 12px; }
        .meta td, .summary td { border: 1px solid #cbd5e1; padding: 6px 8px; width: 33.33%; vertical-align: top; }
        .label { color: #64748b; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.marks { margin: 12px 0; }
        .marks th, .marks td { border: 1px solid #cbd5e1; padding: 6px 7px; vertical-align: middle; }
        .marks th { background: #f1f5f9; color: #1e293b; font-weight: bold; }
        .center { text-align: center; }
        .signatures { margin-top: 35px; text-align: center; page-break-inside: avoid; }
        .signatures td { width: 33.33%; padding: 0 16px; border: none; }
        .line { border-top: 1px solid #94a3b8; padding-top: 6px; font-weight: bold; font-size: 9.5px; }
        .muted { color: #64748b; }
        .col-sub { width: 34%; }
        .col-num { width: 11%; }
        .col-cred { width: 10%; }
        .col-gp { width: 12%; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $institution['name'] }}</h1>
        @if ($institution['address']) <p>{{ $institution['address'] }}</p> @endif
        @if ($institution['phone'] || $institution['email'])
            <p>{{ $institution['phone'] }}{{ $institution['phone'] && $institution['email'] ? ' | ' : '' }}{{ $institution['email'] }}</p>
        @endif
    </header>

    <h2 class="title">Official Student Report Card</h2>
    <p class="center muted" style="margin-bottom: 10px;">{{ $selectedTerm ? $selectedTerm->academicYear->name.' — '.$selectedTerm->name : 'Published results' }}</p>

    <table class="meta">
        <tr>
            <td><span class="label">Student</span><br><strong>{{ $student->user->name }}</strong></td>
            <td><span class="label">Admission Number</span><br><strong>{{ $student->admission_no }}</strong></td>
            <td><span class="label">Class &amp; Section</span><br><strong>{{ $student->class?->full_name ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td><span class="label">Guardian</span><br>{{ $student->guardian?->user?->name ?? '—' }}</td>
            <td><span class="label">Academic Year</span><br>{{ $selectedTerm?->academicYear?->name ?? '—' }}</td>
            <td><span class="label">Term</span><br>{{ $selectedTerm?->name ?? 'All recorded published exams' }}</td>
        </tr>
    </table>

    <table class="marks">
        <thead>
            <tr>
                <th class="col-sub">Subject / Exam</th>
                <th class="col-cred center">Credits</th>
                <th class="col-num center">Maximum</th>
                <th class="col-num center">Obtained</th>
                <th class="col-num center">Percent</th>
                <th class="col-num center">Grade</th>
                <th class="col-gp center">Grade Point</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalObtained = 0;
                $totalMaximum = 0;
            @endphp
            @if ($termResult)
                @forelse ($subjectResults as $subjectResult)
                    @php
                        $credits = $subjectResult->credit_hours ?? 0;
                        $totalMaximum += $credits;
                    @endphp
                    <tr>
                        <td class="col-sub">{{ $subjectResult->subject->name }}<br><span class="muted">{{ $subjectResult->subject->code }} — Term result</span></td>
                        <td class="col-cred center">{{ $credits ?: '—' }}</td>
                        <td class="col-num center">100</td>
                        <td class="col-num center">{{ $subjectResult->result_status === 'graded' ? \App\Support\SchoolHelper::formatMarks($subjectResult->percentage) : ucfirst($subjectResult->result_status) }}</td>
                        <td class="col-num center">{{ $subjectResult->percentage === null ? '—' : $subjectResult->percentage.'%' }}</td>
                        <td class="col-num center">{{ $subjectResult->result_status === 'graded' ? $subjectResult->grade : ucfirst($subjectResult->result_status) }}</td>
                        <td class="col-gp center">{{ $subjectResult->grade_point === null ? '—' : number_format((float) $subjectResult->grade_point, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">No subject results are available for this term.</td></tr>
                @endforelse
            @else
                @forelse ($results as $result)
                    @php
                        $subject = $result->exam->subject;
                        $credits = $subject->credit_hours ?? 3;
                        $maximum = $result->exam->max_marks;
                        $percentage = $result->is_absent ? 0 : \App\Support\SchoolHelper::resultPercentage($result->marks, $maximum);
                        $grade = \App\Support\SchoolHelper::gradeFor($percentage, $gradeScale);
                        $gradePoint = \App\Support\SchoolHelper::gradePointFor($percentage, $gradeScale);
                        $totalMaximum += $maximum;
                        if (! $result->is_absent) {
                            $totalObtained += (float) $result->marks;
                        }
                    @endphp
                    <tr>
                        <td class="col-sub">{{ $subject->name }}<br><span class="muted">{{ $subject->code }} — {{ $result->exam->name }}</span></td>
                        <td class="col-cred center">{{ $credits }}</td>
                        <td class="col-num center">{{ $maximum }}</td>
                        <td class="col-num center">{{ $result->is_absent ? ($result->is_excused ? 'Excused' : 'Absent') : \App\Support\SchoolHelper::formatMarks($result->marks) }}</td>
                        <td class="col-num center">{{ $result->is_absent ? '—' : $percentage.'%' }}</td>
                        <td class="col-num center">{{ $grade }}</td>
                        <td class="col-gp center">{{ number_format($gradePoint, 1) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">No published results available for this term.</td></tr>
                @endforelse
            @endif
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><span class="label">{{ $termResult ? 'Final Result' : 'Aggregate Marks' }}</span><br><strong>{{ $termResult ? ucfirst($termResult->result_status) : $totalObtained.' / '.$totalMaximum }}</strong></td>
            <td><span class="label">Term GPA</span><br><strong>{{ $gpa !== null ? number_format($gpa, 2).' / '.number_format((float) $gradePointMax, 2) : '—' }}</strong></td>
            <td><span class="label">Attendance</span><br><strong>{{ $attendance['present'] }} present, {{ $attendance['late'] }} late / {{ $attendance['total'] }} days ({{ $attendance['percentage'] }}%)</strong></td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="line">Class Teacher</div></td>
            <td><div class="line">Institution Administrator</div></td>
            <td><div class="line">Guardian</div></td>
        </tr>
    </table>
</body>
</html>
