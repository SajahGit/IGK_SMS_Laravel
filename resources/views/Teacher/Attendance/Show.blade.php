<x-layout title="Mark sheet">
    <x-ui.page-header
        :title="$session->classSubject->section->full_name.' / '.$session->classSubject->subject->name"
        :subtitle="'Attendance for '.$session->session_date->format('l, d M Y')">
        <a href="{{ route('teacher.attendance.index') }}" class="btn btn-secondary">Back</a>
    </x-ui.page-header>

    @if ($students->isEmpty())
        <x-ui.empty-state message="No active students in this section." />
    @else
        <form method="POST" action="{{ route('teacher.attendance.records.update', $session) }}">
            @csrf @method('PUT')

            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm"
                    onclick="document.querySelectorAll('input[type=radio][value=present]').forEach(r => r.checked = true)">
                    Mark all present
                </button>
                <span class="text-xs text-slate-500">Then change only the students who are not present.</span>
            </div>

            <x-ui.table>
                <x-slot:head><th>Admission no</th><th>Name</th><th>Status</th><th>Remarks</th></x-slot:head>
                @foreach ($students as $s)
                    @php $current = old("status.{$s->id}", $records->get($s->id)?->status ?? 'present'); @endphp
                    <tr>
                        <td>{{ $s->admission_no }}</td>
                        <td class="whitespace-nowrap">{{ $s->full_name }}</td>
                        <td>
                            <div class="flex flex-wrap gap-3">
                                @foreach (config('school.attendance_statuses') as $status)
                                    <label class="inline-flex items-center gap-1 text-sm">
                                        <input type="radio" name="status[{{ $s->id }}]" value="{{ $status }}" @checked($current === $status)>
                                        {{ ucfirst($status) }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <input type="text" name="remarks[{{ $s->id }}]" maxlength="255" class="input"
                                   value="{{ old("remarks.{$s->id}", $records->get($s->id)?->remarks) }}">
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save attendance</button>
            </div>
        </form>
    @endif
</x-layout>
