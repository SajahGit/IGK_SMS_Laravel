<x-layout title="New exam">
    <x-ui.page-header title="New exam" :subtitle="$term ? 'Term: '.$term->full_name : null">
        <a href="{{ route('teacher.exams.index') }}" class="btn btn-secondary">Back</a>
    </x-ui.page-header>

    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ route('teacher.exams.store') }}" class="space-y-4">
            @csrf
            <x-ui.field name="name" label="Exam name (e.g. Mid Term Exam)" required />
            <x-ui.field name="type" label="Type" type="select" required
                :options="collect(config('school.exam_types'))->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all()" />
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Create exam</button>
                <a href="{{ route('teacher.exams.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-layout>
