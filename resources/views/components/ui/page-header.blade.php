@props(['title', 'subtitle' => null])
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold text-slate-900">{{ $title }}</h1>
        @if ($subtitle)<p class="text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    <div class="no-print flex flex-wrap gap-2">{{ $slot }}</div>
</div>
