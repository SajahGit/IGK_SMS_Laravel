<x-layout title="Attendance">
    <x-ui.page-header title="Attendance sessions">
        <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary">+ Take attendance</a>
    </x-ui.page-header>

    <x-ui.filter-bar>
        <div>
            <label class="label" for="class_subject_id">Class subject</label>
            <select id="class_subject_id" name="class_subject_id" class="input">
                <option value="">All my classes</option>
                @foreach ($mine as $cs)
                    <option value="{{ $cs->id }}" @selected((string) request('class_subject_id') === (string) $cs->id)>{{ $cs->label }}</option>
                @endforeach
            </select>
        </div>
    </x-ui.filter-bar>

    @if ($sessions->isEmpty())
        <x-ui.empty-state message="No attendance has been taken yet.">
            <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary btn-sm">Take attendance</a>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head><th>Date</th><th>Section</th><th>Subject</th><th>Students marked</th><th>Status</th><th class="text-right">Actions</th></x-slot:head>
            @foreach ($sessions as $s)
                <tr>
                    <td>{{ $s->session_date->format('Y-m-d') }}</td>
                    <td>{{ $s->classSubject->section->full_name }}</td>
                    <td>{{ $s->classSubject->subject->name }}</td>
                    <td>{{ $s->records_count }}</td>
                    <td><x-ui.badge :color="$s->status === 'submitted' ? 'green' : 'amber'">{{ ucfirst($s->status) }}</x-ui.badge></td>
                    <td class="text-right"><a class="btn btn-secondary btn-sm" href="{{ route('teacher.attendance.show', $s) }}">Open</a></td>
                </tr>
            @endforeach
        </x-ui.table>
        <div class="mt-4">{{ $sessions->links() }}</div>
    @endif
</x-layout>
