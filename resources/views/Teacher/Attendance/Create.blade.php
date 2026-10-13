<x-layout title="Take attendance">
    <x-ui.page-header title="Take attendance">
        <a href="{{ route('teacher.attendance.index') }}" class="btn btn-secondary">Back</a>
    </x-ui.page-header>

    @if ($mine->isEmpty())
        <x-ui.empty-state message="No classes are assigned to you for this term. Ask the admin." />
    @else
        <x-ui.card class="max-w-xl">
            <form method="POST" action="{{ route('teacher.attendance.store') }}" class="space-y-4">
                @csrf
                <x-ui.field name="class_subject_id" label="Class and subject" type="select" required
                    :options="$mine->mapWithKeys(fn ($cs) => [$cs->id => $cs->label])->all()" />
                <x-ui.field name="session_date" label="Date" type="date" :value="now()->toDateString()" required />
                <button type="submit" class="btn btn-primary">Open mark sheet</button>
            </form>
        </x-ui.card>
    @endif
</x-layout>
