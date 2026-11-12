@extends('Layouts.LayoutApp')
@section('title', 'Admin Dashboard')
@section('content')
<div class="mb-4"><h3 class="mb-1">Admin Dashboard</h3><p class="text-muted mb-0">Overview of your school at a glance.</p></div>

<div class="row g-3 mb-4">
    @foreach ([['Students', $studentCount, 'admin.students.index', 'people-fill', 'indigo'], ['Teachers', $teacherCount, 'admin.teachers.index', 'person-workspace', 'cyan'],
               ['Guardians', $guardianCount, 'admin.guardians.index', 'person-hearts', 'rose'], ['Classes', $classCount, 'admin.classes.index', 'building', 'green'],
               ['Subjects', $subjectCount, 'admin.subjects.index', 'book-fill', 'amber']] as [$label, $count, $route, $icon, $tone])
        <div class="col-6 col-xl">
            <a href="{{ route($route) }}" class="text-decoration-none d-block h-100">
                @include('Layouts.LayoutStat', ['icon' => $icon, 'value' => $count, 'label' => $label, 'tone' => $tone])
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-calendar2-check me-2"></i>Attendance trend (7 days)</div>
            <div class="card-body">
                <canvas id="attendanceTrendChart" role="img" aria-label="Present and absent students over the last seven days"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Present</th><th>Absent</th></tr></thead>
                        <tbody>
                            @foreach ($attendanceTrend['labels'] as $index => $label)
                                <tr><td>{{ $label }}</td><td>{{ $attendanceTrend['present'][$index] }}</td><td>{{ $attendanceTrend['absent'][$index] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Fee collection (6 months)</div>
            <div class="card-body">
                <canvas id="feeCollectionChart" role="img" aria-label="Monthly fee collections excluding reversed payments"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Month</th><th>Collected</th></tr></thead>
                        <tbody>
                            @foreach ($feeCollectionTrend['labels'] as $index => $label)
                                <tr><td>{{ $label }}</td><td>{{ number_format($feeCollectionTrend['totals'][$index], 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-person-plus me-2"></i>Latest students</span>
        <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add student</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Admission No</th><th>Name</th><th>Class</th></tr></thead>
            <tbody>
            @forelse ($latestStudents as $s)
                <tr><td>{{ $s->admission_no }}</td><td>{{ $s->user->name }}</td><td>{{ $s->class?->full_name ?? 'Unassigned' }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No students yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    if (window.Chart) {
        const attendance = @json($attendanceTrend);
        const fees = @json($feeCollectionTrend);
        new Chart(document.getElementById('attendanceTrendChart'), {
            type: 'line',
            data: {
                labels: attendance.labels,
                datasets: [
                    { label: 'Present', data: attendance.present, borderColor: '#22c55e', backgroundColor: '#22c55e', tension: 0.3 },
                    { label: 'Absent', data: attendance.absent, borderColor: '#f87171', backgroundColor: '#f87171', tension: 0.3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
        new Chart(document.getElementById('feeCollectionChart'), {
            type: 'bar',
            data: {
                labels: fees.labels,
                datasets: [{ label: 'Collected', data: fees.totals, backgroundColor: '#6366f1' }],
            },
            options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } },
        });
    }
</script>
@endsection
