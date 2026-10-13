<x-layout title="My profile">
    <x-ui.page-header title="My profile" />

    <div class="grid gap-5 md:grid-cols-2">
        <x-ui.card title="Student details">
            <dl class="grid grid-cols-3 gap-y-2 text-sm">
                <dt class="text-slate-500">Name</dt><dd class="col-span-2">{{ $student->full_name }}</dd>
                <dt class="text-slate-500">Admission no</dt><dd class="col-span-2">{{ $student->admission_no }}</dd>
                <dt class="text-slate-500">Section</dt><dd class="col-span-2">{{ $student->section?->full_name ?? 'Not placed' }}</dd>
                <dt class="text-slate-500">Email</dt><dd class="col-span-2">{{ $student->user->email }}</dd>
                <dt class="text-slate-500">Date of birth</dt><dd class="col-span-2">{{ $student->dob?->format('d M Y') ?? '-' }}</dd>
                <dt class="text-slate-500">Gender</dt><dd class="col-span-2">{{ $student->gender ?? '-' }}</dd>
                <dt class="text-slate-500">Status</dt><dd class="col-span-2">{{ ucfirst($student->status) }}</dd>
                <dt class="text-slate-500">Parents</dt>
                <dd class="col-span-2">{{ $student->guardians->map(fn ($g) => $g->name.' ('.$g->relationship.')')->implode(', ') ?: '-' }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card title="Contact details (you can change these)">
            <form method="POST" action="{{ route('student.profile.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <x-ui.field name="phone" label="Phone" :value="$student->user->phone" />
                <x-ui.field name="address" label="Address" type="textarea" :value="$student->address" />
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </x-ui.card>
    </div>
</x-layout>
