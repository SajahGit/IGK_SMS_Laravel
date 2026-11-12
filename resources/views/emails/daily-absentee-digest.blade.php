<h1>Daily student absentee report</h1>
<p>Attendance date: {{ $attendanceDate }}</p>

@if ($absentees->isEmpty())
    <p>No student absences were recorded for this date.</p>
@else
    <table cellpadding="6" cellspacing="0" border="1">
        <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class</th>
                <th>Subject / Session</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($absentees as $absentee)
                <tr>
                    <td>{{ $absentee['admission_no'] }}</td>
                    <td>{{ $absentee['student_name'] }}</td>
                    <td>{{ $absentee['class_name'] }}</td>
                    <td>{{ $absentee['subject_name'] }}</td>
                    <td>{{ $absentee['remarks'] ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
