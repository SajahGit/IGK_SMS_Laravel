@extends('Layouts.LayoutApp')
@section('title', 'Registrar Dashboard')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-1">Registrar Dashboard</h3>
        <p class="text-muted mb-0">Admissions, academic records, and result publication overview.</p>
    </div>
    <a href="{{ route('reports.enrollment') }}" class="btn btn-outline-primary">Academic Reports</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'people-fill', 'value' => $studentCount, 'label' => 'Enrolled Students', 'tone' => 'primary'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'building', 'value' => $classCount, 'label' => 'Classes', 'tone' => 'info'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'calendar-range', 'value' => $academicYear?->name ?? 'Not set', 'label' => 'Current Academic Year', 'tone' => 'success'])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('Layouts.LayoutStat', ['icon' => 'journal-check', 'value' => $pendingExams, 'label' => 'Results Awaiting Publication', 'tone' => 'warning'])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recently Admitted Students</h5>
        <span class="text-muted">{{ $term?->name ?? 'No current term' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Admission No</th><th>Student</th><th>Class</th><th>Admitted</th></tr></thead>
            <tbody>
                @forelse ($latestStudents as $student)
                    <tr>
                        <td>{{ $student->admission_no }}</td>
                        <td>{{ $student->user->name }}</td>
                        <td>{{ $student->class->full_name }}</td>
                        <td>{{ $student->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No student admissions have been recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
