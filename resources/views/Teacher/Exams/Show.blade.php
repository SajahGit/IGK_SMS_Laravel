<x-layout :title="$exam->name">
    <x-ui.page-header :title="$exam->name"
        :subtitle="ucfirst($exam->type).' - '.$exam->term->full_name.' - created by '.($exam->creator?->name ?? 'unknown')">
        <a href="{{ route('teacher.exams.index') }}" class="btn btn-secondary">Back</a>
        @if ($exam->isPublished())
            <x-ui.badge color="green">Published</x-ui.badge>
        @elseif ($canPublish)
            <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}"
                  onsubmit="return confirm('Publish results? Marks will be locked and students and parents will see the results.')">
                @csrf
                <button type="submit" class="btn btn-primary">Publish results</button>
            </form>
            <x-ui.confirm-button :action="route('teacher.exams.destroy', $exam)" label="Delete exam" message="Delete this exam and all its marks?" />
        @endif
    </x-ui.page-header>

    <x-ui.card title="Subjects in this exam">
        @if ($exam->examSubjects->isEmpty())
            <x-ui.empty-state message="No subject has been added yet." />
        @else
            <x-ui.table>
                <x-slot:head><th>Section</th><th>Subject</th><th>Teacher</th><th>Max</th><th>Pass</th><th>Date</th><th>Marks entered</th><th class="text-right">Actions</th></x-slot:head>
                @foreach ($exam->examSubjects as $es)
                    @php $mine = in_array($es->class_subject_id, $myIds, true); @endphp
                    <tr>
                        <td>{{ $es->classSubject->section->full_name }}</td>
                        <td>{{ $es->classSubject->subject->name }}</td>
                        <td>{{ $es->classSubject->staff?->user?->name ?? '-' }}</td>
                        <td>{{ $es->max_marks }}</td>
                        <td>{{ $es->pass_marks }}</td>
                        <td>{{ $es->exam_date?->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ $es->marks_count }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($mine)
                                <a class="btn btn-primary btn-sm" href="{{ route('teacher.marks.show', $es) }}">
                                    {{ $exam->isPublished() ? 'View marks' : 'Enter marks' }}
                                </a>
                                @unless ($exam->isPublished())
                                    <x-ui.confirm-button :action="route('teacher.exam-subjects.destroy', $es)" label="Remove"
                                        message="Remove this subject and its marks from the exam?" />
                                @endunless
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>

    @unless ($exam->isPublished())
        <x-ui.card title="Add one of my subjects to this exam" class="max-w-3xl">
            @if ($available->isEmpty())
                <p class="text-sm text-slate-500">All of your class subjects are already in this exam, or none are assigned to you.</p>
            @else
                <form method="POST" action="{{ route('teacher.exams.subjects.store', $exam) }}" class="grid gap-3 md:grid-cols-4 items-end">
                    @csrf
                    <x-ui.field name="class_subject_id" label="Class and subject" type="select" required
                        :options="$available->mapWithKeys(fn ($cs) => [$cs->id => $cs->label])->all()" />
                    <x-ui.field name="max_marks" label="Max marks" type="number" :value="100" required />
                    <x-ui.field name="pass_marks" label="Pass marks" type="number" :value="40" required />
                    <x-ui.field name="exam_date" label="Exam date" type="date" />
                    <div class="md:col-span-4"><button type="submit" class="btn btn-primary">Add subject</button></div>
                </form>
            @endif
        </x-ui.card>
    @endunless
</x-layout>
