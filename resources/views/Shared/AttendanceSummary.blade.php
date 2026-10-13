{{-- needs: $summary = StudentReportService::attendanceSummary() --}}
@php $o = $summary['overall']; $low = config('school.low_attendance_threshold'); @endphp

<div class="grid grid-cols-2 gap-3 md:grid-cols-4 mb-5">
    <x-ui.stat label="Overall attendance" :value="$o['total'] > 0 ? $o['percentage'].'%' : '-'"
        :hint="$o['total'] > 0 && $o['percentage'] < $low ? 'Below '.$low.'% - needs attention' : null" />
    <x-ui.stat label="Sessions recorded" :value="$o['total']" />
    <x-ui.stat label="Attended (present + late)" :value="$o['attended']" />
    <x-ui.stat label="Missed" :value="$o['total'] - $o['attended']" />
</div>

<x-ui.card title="Attendance by subject">
    @if ($summary['rows']->isEmpty())
        <x-ui.empty-state message="No attendance has been recorded yet." />
    @else
        <x-ui.table>
            <x-slot:head><th>Subject</th><th>Sessions</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th><th>Attendance</th></x-slot:head>
            @foreach ($summary['rows'] as $r)
                <tr>
                    <td>{{ $r->subject }}</td>
                    <td>{{ $r->total }}</td>
                    <td>{{ $r->present }}</td>
                    <td>{{ $r->late }}</td>
                    <td>{{ $r->absent }}</td>
                    <td>{{ $r->excused }}</td>
                    <td><x-ui.badge :color="$r->percentage < $low ? 'red' : 'green'">{{ $r->percentage }}%</x-ui.badge></td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-ui.card>
