<x-layout title="Class students">
    <x-ui.page-header :title="$classSubject->section->full_name.' / '.$classSubject->subject->name"
        subtitle="Students of this class with their attendance in this subject">
        <a href="{{ route('teacher.my-classes.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary">Take attendance</a>
    </x-ui.page-header>

    @if ($students->isEmpty())
        <x-ui.empty-state message="No active students in this section." />
    @else
        <x-ui.table>
            <x-slot:head><th>Admission no</th><th>Name</th><th>Sessions</th><th>Attendance</th></x-slot:head>
            @foreach ($students as $s)
                @php
                    $st = $stats->get($s->id);
                    $pct = $st && $st->total > 0 ? round($st->attended / $st->total * 100) : null;
                @endphp
                <tr>
                    <td>{{ $s->admission_no }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $st->total ?? 0 }}</td>
                    <td>
                        @if ($pct === null) <span class="text-slate-400">-</span>
                        @else
                            <x-ui.badge :color="$pct < config('school.low_attendance_threshold') ? 'red' : 'green'">{{ $pct }}%</x-ui.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layout>
