@props(['type' => 'info'])
@php
    $styles = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'mb-4 rounded-lg border px-4 py-3 text-sm '.($styles[$type] ?? $styles['info'])]) }} role="alert">
    {{ $slot }}
</div>
