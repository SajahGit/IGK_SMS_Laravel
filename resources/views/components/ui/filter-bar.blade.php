@props(['action' => null])
<form method="GET" action="{{ $action ?? url()->current() }}"
      class="no-print mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-3">
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ $action ?? url()->current() }}" class="btn btn-secondary btn-sm">Clear</a>
    </div>
</form>
