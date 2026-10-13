<x-layout title="My dashboard">
    <x-ui.page-header :title="'Welcome, '.$student->first_name"
        :subtitle="$student->admission_no.' - '.($student->section?->full_name ?? 'Not placed in a section')">
        @if (Route::has('reports.card'))
            <a href="{{ route('reports.card', $student) }}" class="btn btn-secondary">My report card</a>
        @endif
    </x-ui.page-header>

    @php $o = $attendance['overall']; $low = config('school.low_attendance_threshold'); @endphp
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 mb-5">
        <x-ui.stat label="Attendance" :value="$o['total'] > 0 ? $o['percentage'].'%' : '-'"
            :hint="$o['total'] > 0 && $o['percentage'] < $low ? 'Below '.$low.'%' : null" />
        <x-ui.stat label="Sessions recorded" :value="$o['total']" />
        <x-ui.stat label="Latest GPA" :value="$latest ? number_format($latest['result']->gpa, 2) : '-'"
            :hint="$latest ? $latest['exam']->name : 'No published results yet'" />
        <x-ui.stat label="Subjects passed"
            :value="$latest ? $latest['result']->subjects_passed.' / '.$latest['result']->subjects_total : '-'" />
    </div>

    <x-ui.card title="Latest result">
        @if (! $latest)
            <x-ui.empty-state message="No results have been published yet." />
        @else
            <x-ui.table>
                <x-slot:head><th>Subject</th><th>Marks</th><th>Grade</th></x-slot:head>
                @foreach ($latest['subjects'] as $sr)
                    <tr>
                        <td>{{ $sr->classSubject?->subject?->name }}</td>
                        <td>{{ $sr->is_absent ? 'Absent' : rtrim(rtrim(number_format($sr->marks_obtained, 2, '.', ''), '0'), '.').' / '.$sr->max_marks }}</td>
                        <td><strong>{{ $sr->grade }}</strong></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <a class="btn btn-secondary btn-sm mt-3" href="{{ route('student.results.index') }}">All results</a>
        @endif
    </x-ui.card>
</x-layout>
