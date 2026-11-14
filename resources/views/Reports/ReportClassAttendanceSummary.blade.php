@extends('Layouts.LayoutApp')
@section('title', 'Class Attendance Summary')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Class Attendance Summary Report</h3>
        <small class="text-muted">R5 · Session count, average attendance and students below threshold.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.pdf', ['report' => 'class-attendance'] + request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('reports.class-attendance.csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download CSV
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('reports.class-attendance') }}" class="row g-2 align-items-center">
            <div class="col-md-2">
                <label for="class_id" class="form-label small fw-semibold">Class/Section</label>
                <select name="class_id" id="class_id" class="form-select">
                    <option value="">All sections</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="term_id" class="form-label small fw-semibold">Term</label>
                <select name="term_id" id="term_id" class="form-select">
                    <option value="">All terms</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="subject_id" class="form-label small fw-semibold">Subject</label>
                <select name="subject_id" id="subject_id" class="form-select">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($subjectId == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="from" class="form-label small fw-semibold">From Date</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-1">
                <label for="threshold" class="form-label small fw-semibold">Threshold %</label>
                <input type="number" min="0" max="100" step="1" name="threshold" id="threshold" class="form-control" value="{{ $threshold }}">
            </div>
            <div class="col-md-1 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i> Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="print-nowrap">Class & Section</th>
                    <th>Subject</th>
                    <th class="text-center">Sessions Held</th>
                    <th class="text-center">Average Attendance</th>
                    <th class="text-center">Students Below {{ $threshold }}%</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary as $row)
                    <tr>
                        <td>
                            <span class="fw-bold print-nowrap">{{ $row['class']->full_name }}</span>
                        </td>
                        <td>{{ $row['subject'] }}</td>
                        <td class="text-center">{{ $row['sessions'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $row['percentage'] >= $threshold ? 'success' : 'danger' }} fs-6">
                                {{ $row['percentage'] }}%
                            </span>
                        </td>
                        <td class="text-center">{{ $row['below_threshold'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No attendance data recorded for this date window.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
