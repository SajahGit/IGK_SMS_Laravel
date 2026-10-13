@props(['title' => null])
<div {{ $attributes->merge(['class' => 'mb-5 rounded-lg border border-slate-200 bg-white']) }}>
    @if ($title)
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">{{ $title }}</div>
    @endif
    <div class="p-4">{{ $slot }}</div>
</div>
