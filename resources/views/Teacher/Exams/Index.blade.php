<x-layout title="Exams">
    <x-ui.page-header title="Exams & marks">
        <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary">+ New exam</a>
    </x-ui.page-header>

    @if ($exams->isEmpty())
        <x-ui.empty-state message="No exams yet.">
            <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary btn-sm">Create the first exam</a>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <x-slot:head><th>Exam</th><th>Type</th><th>Subjects</th><th>Created by</th><th>Status</th><th class="text-right">Actions</th></x-slot:head>
            @foreach ($exams as $e)
                <tr>
                    <td>{{ $e->name }}</td>
                    <td>{{ ucfirst($e->type) }}</td>
                    <td>{{ $e->exam_subjects_count }}</td>
                    <td>{{ $e->creator?->name ?? '-' }}</td>
                    <td><x-ui.badge :color="$e->isPublished() ? 'green' : 'amber'">{{ ucfirst($e->status) }}</x-ui.badge></td>
                    <td class="text-right"><a class="btn btn-secondary btn-sm" href="{{ route('teacher.exams.show', $e) }}">Open</a></td>
                </tr>
            @endforeach
        </x-ui.table>
        <div class="mt-4">{{ $exams->links() }}</div>
    @endif
</x-layout>
