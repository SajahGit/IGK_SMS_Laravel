<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $student->admission_no }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #244c86; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { color: #244c86; font-size: 20px; margin-bottom: 5px; }
        .header p { color: #526179; margin-top: 3px; }
        .title { text-align: center; margin: 12px 0; font-size: 15px; }
        .meta, .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td, .summary td { border: 1px solid #d5dce7; padding: 7px; }
        .label { color: #526179; font-size: 9px; }
        table.marks { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .marks th, .marks td { border: 1px solid #cbd4e1; padding: 6px; }
        .marks th { background: #edf2f8; text-align: left; }
        .center { text-align: center; }
        .signatures { width: 100%; margin-top: 45px; text-align: center; }
        .signatures td { width: 33%; padding: 0 12px; }
        .line { border-top: 1px solid #7b8798; padding-top: 6px; }
        .muted { color: #64748b; }
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
    <p class="center muted">{{ $selectedTerm ? $selectedTerm->academicYear->name.' - '.$selectedTerm->name : 'Published results' }}</p>

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
                <th>Subject / Exam</th>
                <th class="center">Credits</th>
                <th class="center">Maximum</th>
                <th class="center">Obtained</th>
                <th class="center">Percent</th>
                <th class="center">Grade</th>
                <th class="center">Grade Point</th>
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
                        <td>{{ $subjectResult->subject->name }}<br><span class="muted">{{ $subjectResult->subject->code }} - Term result</span></td>
                        <td class="center">{{ $credits ?: '—' }}</td>
                        <td class="center">—</td>
                        <td class="center">{{ $subjectResult->result_status === 'excused' ? 'Excused' : '—' }}</td>
                        <td class="center">{{ $subjectResult->percentage === null ? '—' : $subjectResult->percentage.'%' }}</td>
                        <td class="center">{{ $subjectResult->result_status === 'graded' ? $subjectResult->grade : ucfirst($subjectResult->result_status) }}</td>
                        <td class="center">{{ $subjectResult->grade_point === null ? '—' : number_format((float) $subjectResult->grade_point, 2) }}</td>
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
                        <td>{{ $subject->name }}<br><span class="muted">{{ $subject->code }} - {{ $result->exam->name }}</span></td>
                        <td class="center">{{ $credits }}</td>
                        <td class="center">{{ $maximum }}</td>
                        <td class="center">{{ $result->is_absent ? ($result->is_excused ? 'Excused' : 'Absent') : \App\Support\SchoolHelper::formatMarks($result->marks) }}</td>
                        <td class="center">{{ $result->is_absent ? '—' : $percentage.'%' }}</td>
                        <td class="center">{{ $grade }}</td>
                        <td class="center">{{ number_format($gradePoint, 1) }}</td>
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
