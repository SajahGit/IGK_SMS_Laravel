<x-layout title="Enter marks">
    <x-ui.page-header
        :title="$examSubject->exam->name.' - '.$examSubject->classSubject->subject->name"
        :subtitle="$examSubject->classSubject->section->full_name.' - maximum '.$examSubject->max_marks.', pass '.$examSubject->pass_marks">
        <a href="{{ route('teacher.exams.show', $examSubject->exam) }}" class="btn btn-secondary">Back to exam</a>
    </x-ui.page-header>

    @if ($locked)
        <x-ui.alert type="info">Results are published, so these marks are locked.</x-ui.alert>
    @endif

    @if ($students->isEmpty())
        <x-ui.empty-state message="No active students in this section." />
    @else
        <form method="POST" action="{{ route('teacher.marks.store', $examSubject) }}">
            @csrf
            <x-ui.table>
                <x-slot:head><th>Admission no</th><th>Name</th><th>Marks (0 - {{ $examSubject->max_marks }})</th><th>Absent</th></x-slot:head>
                @foreach ($students as $s)
                    @php
                        $m = $marks->get($s->id);
                        $value = old("marks.{$s->id}", $m?->marks_obtained !== null ? rtrim(rtrim((string) $m->marks_obtained, '0'), '.') : '');
                        $absent = session()->hasOldInput() ? (bool) old("absent.{$s->id}") : (bool) ($m?->is_absent);
                    @endphp
                    <tr>
                        <td>{{ $s->admission_no }}</td>
                        <td class="whitespace-nowrap">{{ $s->full_name }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $examSubject->max_marks }}"
                                   name="marks[{{ $s->id }}]" value="{{ $value }}" class="input" style="max-width: 8rem"
                                   @disabled($locked)>
                        </td>
                        <td>
                            <input type="checkbox" name="absent[{{ $s->id }}]" value="1" @checked($absent) @disabled($locked)>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            @unless ($locked)
                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">Save marks</button>
                    <span class="text-xs text-slate-500">Leave a mark empty if it is not entered yet.</span>
                </div>
            @endunless
        </form>
    @endif
</x-layout>
