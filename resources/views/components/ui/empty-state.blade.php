@props(['message' => 'Nothing to show yet.'])
<div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
    <p>{{ $message }}</p>
    @if (trim((string) $slot) !== '')
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
