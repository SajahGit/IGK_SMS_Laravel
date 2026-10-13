@props(['color' => 'slate'])
@php
    $styles = [
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'slate' => 'bg-slate-200 text-slate-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold '.($styles[$color] ?? $styles['slate'])]) }}>{{ $slot }}</span>
