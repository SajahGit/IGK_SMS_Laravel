@props(['label', 'value', 'hint' => null])
<div class="rounded-lg border border-slate-200 bg-white p-4">
    <div class="text-xs font-semibold text-slate-500">{{ $label }}</div>
    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</div>
    @if ($hint)<div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>@endif
</div>
