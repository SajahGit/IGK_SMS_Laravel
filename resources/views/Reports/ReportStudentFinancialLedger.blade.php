@extends('Layouts.LayoutApp')
@section('title', 'Student Financial Ledger')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Student Financial Ledger</h3>
    <div class="no-print">
        @if ($selectedStudent)
        <a href="{{ route($isStudent ? 'student.ledger.csv' : $reportRoutePrefix.'.student-ledger.csv', ['student_id' => $selectedStudent->id, 'academic_year_id' => $academicYearId]) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
        @endif
    </div>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    @unless ($isStudent)
    <div class="col-md-4">
        <label class="form-label visually-hidden">Student</label>
        <select name="student_id" class="form-select">
            <option value="">Select student</option>
            @foreach ($students as $s)
                <option value="{{ $s->id }}" @selected($studentId == $s->id)>{{ $s->admission_no }} - {{ $s->user->name }}</option>
            @endforeach
        </select>
    </div>
    @endunless
    <div class="col-md-3">
        <label class="form-label visually-hidden">Academic Year</label>
        <select name="academic_year_id" class="form-select">
            <option value="">All academic years</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected($academicYearId == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
    </div>
</form>

@if ($selectedStudent)
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <h5 class="card-title mb-1">Ledger for {{ $selectedStudent->user->name }} ({{ $selectedStudent->admission_no }})</h5>
        <p class="card-text text-muted small">{{ $selectedStudent->class->full_name ?? 'No class assigned' }} @if($academicYearId) · Academic Year: {{ $academicYears->firstWhere('id', $academicYearId)?->name }} @endif</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Date</th><th>Description</th><th>Type</th><th>Charge</th><th>Payment</th><th>Running Balance</th></tr></thead>
            <tbody>
            @forelse ($ledger as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ ucfirst($row['type']) }}</td>
                    <td>{{ $row['type'] === 'charge' ? $row['amount'] : '—' }}</td>
                    <td>{{ $row['type'] === 'payment' ? $row['amount'] : '—' }}</td>
                    <td>{{ $row['running_balance'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No ledger entries found for the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="alert alert-info">Select a student to view their financial ledger (charges and payments with running balance).</div>
@endif
@endsection
