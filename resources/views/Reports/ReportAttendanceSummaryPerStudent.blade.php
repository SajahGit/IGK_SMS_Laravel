@extends('Layouts.LayoutApp')
@section('title', 'Attendance Summary per Student')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Attendance Summary per Student</h3>
    <div class="no-print">
        <a href="{{ route('reports.pdf', ['report' => 'attendance-summary'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.attendance-summary.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    </div>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-2">
        <label class="form-label visually-hidden">Class</label>
        <select name="class_id" class="form-select">
            <option value="">All classes</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label visually-hidden">Term</label>
        <select name="term_id" class="form-select">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label visually-hidden">Subject</label>
        <select name="subject_id" class="form-select">
            <option value="">All subjects</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected($subjectId == $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label visually-hidden">Student</label>
        <select name="student_id" class="form-select">
            <option value="">All students</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}" @selected($studentId == $student->id)>{{ $student->admission_no }} — {{ $student->user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label class="form-label visually-hidden">From</label>
        <input type="date" name="from" class="form-control" value="{{ $from }}">
    </div>
    <div class="col-md-1">
        <label class="form-label visually-hidden">To</label>
        <input type="date" name="to" class="form-control" value="{{ $to }}">
    </div>
    <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th class="print-nowrap">Admission No</th><th>Student</th><th class="print-nowrap">Sessions Held</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th class="print-nowrap">Attendance %</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="print-nowrap">{{ $row['admission_no'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['sessions_held'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['late'] }}</td>
                    <td>{{ $row['excused'] }}</td>
                    <td>{{ $row['percentage'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No attendance records found for the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
