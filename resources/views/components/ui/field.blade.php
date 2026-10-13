@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'options' => [],
    'required' => false,
    'help' => null,
])
@php
    $label = $label ?? ucfirst(str_replace('_', ' ', $name));
    $dotName = str_replace(['[', ']'], ['.', ''], rtrim($name, '[]'));
    $current = old($dotName, $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($type === 'checkbox')
        @php $checked = session()->hasOldInput() ? (bool) old($dotName) : (bool) $value; @endphp
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="{{ $name }}" value="1" @checked($checked)
                   class="rounded border-slate-300 text-teal-700">
            {{ $label }}
        </label>
    @else
        <label for="f-{{ $dotName }}" class="label">
            {{ $label }} @if ($required)<span class="text-red-600">*</span>@endif
        </label>

        @if ($type === 'select')
            <select id="f-{{ $dotName }}" name="{{ $name }}" class="input" @required($required)>
                <option value="">-- Select --</option>
                @foreach ($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'multiselect')
            @php $selected = array_map('strval', (array) ($current ?? [])); @endphp
            <select id="f-{{ $dotName }}" name="{{ $name }}" class="input" multiple size="6">
                @foreach ($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected(in_array((string) $optValue, $selected, true))>{{ $optLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea id="f-{{ $dotName }}" name="{{ $name }}" rows="3" class="input" @required($required)>{{ $current }}</textarea>
        @else
            <input id="f-{{ $dotName }}" type="{{ $type }}" name="{{ $name }}"
                   value="{{ $type === 'password' ? '' : ($current instanceof \Carbon\CarbonInterface ? $current->format('Y-m-d') : $current) }}"
                   class="input" @required($required)>
        @endif
    @endif

    @if ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($dotName)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
