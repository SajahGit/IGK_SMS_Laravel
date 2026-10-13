<x-layout title="My classes">
    <x-ui.page-header title="My classes" :subtitle="$term ? $term->full_name : null" />

    @if ($items->isEmpty())
        <x-ui.empty-state message="No classes are assigned to you for this term." />
    @else
        <x-ui.table>
            <x-slot:head><th>Section</th><th>Subject</th><th>Students</th><th class="text-right">Actions</th></x-slot:head>
            @foreach ($items as $cs)
                <tr>
                    <td>{{ $cs->section?->full_name }}</td>
                    <td>{{ $cs->subject?->name }}</td>
                    <td>{{ $cs->student_count }}</td>
                    <td class="text-right whitespace-nowrap">
                        <a class="btn btn-secondary btn-sm" href="{{ route('teacher.my-classes.show', $cs) }}">Students</a>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layout>
