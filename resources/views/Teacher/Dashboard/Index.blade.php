<x-layout title="Teacher Dashboard">
    <x-ui.page-header title="Teacher dashboard"
        :subtitle="$term ? 'Current term: '.$term->full_name : 'No term has been created yet'">
        <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary">Take attendance</a>
        <a href="{{ route('teacher.exams.create') }}" class="btn btn-secondary">New exam</a>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 mb-5">
        <x-ui.stat label="My class subjects" :value="$classSubjects->count()" />
        <x-ui.stat label="Attendance taken today" :value="$todaySessions->count()" />
        <x-ui.stat label="Draft exams" :value="$draftExams" hint="Not published yet" />
    </div>

    <x-ui.card title="My classes this term">
        @if ($classSubjects->isEmpty())
            <x-ui.empty-state message="No classes are assigned to you yet. Please ask the admin." />
        @else
            <x-ui.table>
                <x-slot:head><th>Section</th><th>Subject</th><th class="text-right">Actions</th></x-slot:head>
                @foreach ($classSubjects as $cs)
                    <tr>
                        <td>{{ $cs->section?->full_name }}</td>
                        <td>{{ $cs->subject?->name }}</td>
                        <td class="text-right">
                            <a class="btn btn-secondary btn-sm" href="{{ route('teacher.my-classes.show', $cs) }}">Students</a>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    @if ($todaySessions->isNotEmpty())
        <x-ui.card title="Today's attendance sessions">
            <ul class="space-y-2 text-sm">
                @foreach ($todaySessions as $s)
                    <li class="flex items-center justify-between">
                        <span>{{ $s->classSubject->section->full_name }} / {{ $s->classSubject->subject->name }}</span>
                        <a class="btn btn-secondary btn-sm" href="{{ route('teacher.attendance.show', $s) }}">Open</a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</x-layout>
