<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">@include('layouts.LayoutStat', ['icon' => 'percent', 'value' => $summary['percentage'].'%', 'label' => 'Attendance', 'tone' => 'indigo'])</div>
    <div class="col-6 col-lg-3">@include('layouts.LayoutStat', ['icon' => 'check-circle-fill', 'value' => $summary['present'], 'label' => 'Present', 'tone' => 'green'])</div>
    <div class="col-6 col-lg-3">@include('layouts.LayoutStat', ['icon' => 'x-circle-fill', 'value' => $summary['absent'], 'label' => 'Absent', 'tone' => 'rose'])</div>
    <div class="col-6 col-lg-3">@include('layouts.LayoutStat', ['icon' => 'clock-fill', 'value' => $summary['late'], 'label' => 'Late', 'tone' => 'amber'])</div>
</div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($records as $r)
            @php $d = \Illuminate\Support\Carbon::parse($r->date); @endphp
            <tr>
                <td>{{ $d->format('d M Y') }}</td>
                <td>{{ $d->format('l') }}</td>
                <td><span class="badge bg-{{ ['present' => 'success', 'absent' => 'danger', 'late' => 'warning'][$r->status] ?? 'secondary' }} text-capitalize">{{ $r->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">No attendance records.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $records->links('pagination::bootstrap-5') }}</div>
