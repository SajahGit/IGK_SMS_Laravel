@props([
    'name' => 'nationality',
    'value' => '',
    'required' => false,
])
@php
    $nationalities = config('school.demo_nationalities', []);
    $selected = old($name, $value);
    $selectedCode = collect($nationalities)->search(fn ($item) => $item['label'] === $selected);
    $hasError = ($errors ?? null)?->has($name) ?? false;
@endphp
<div class="dropdown international-nationality-picker" data-nationality-picker>
    <button
        type="button"
        class="btn btn-outline-secondary dropdown-toggle international-nationality-toggle{{ $hasError ? ' is-invalid' : '' }}"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-haspopup="listbox"
        aria-label="{{ $selectedCode !== false ? 'Nationality: '.$selected : 'Select nationality' }}"
        aria-required="{{ $required ? 'true' : 'false' }}"
        data-nationality-toggle
    >
        @if ($selectedCode !== false)
            <span class="phone-flag fi fi-{{ $nationalities[$selectedCode]['flag'] }}" aria-hidden="true" data-nationality-flag></span>
        @else
            <span class="phone-flag bi bi-globe" aria-hidden="true" data-nationality-flag></span>
        @endif
        <span data-nationality-label>{{ $selected ?: 'Select nationality' }}</span>
    </button>
    <div class="dropdown-menu international-nationality-menu" role="listbox">
        <div class="p-2">
            <input type="search" class="form-control form-control-sm" placeholder="Search nationality" aria-label="Search nationality" data-nationality-search data-no-auto-filter>
        </div>
        @foreach ($nationalities as $code => $nationality)
            <button
                type="button"
                class="dropdown-item international-nationality-option"
                role="option"
                aria-selected="{{ $selected === $nationality['label'] ? 'true' : 'false' }}"
                data-nationality-option
                data-value="{{ $nationality['label'] }}"
                data-code="{{ $code }}"
                data-flag="{{ $nationality['flag'] }}"
            >
                <span class="phone-flag fi fi-{{ $nationality['flag'] }}" aria-hidden="true"></span>
                <span>{{ $nationality['label'] }}</span>
            </button>
        @endforeach
    </div>
    <select class="visually-hidden" name="{{ $name }}" aria-hidden="true" tabindex="-1" data-nationality-value @required($required)>
        <option value="">Select nationality</option>
        @foreach ($nationalities as $nationality)
            <option value="{{ $nationality['label'] }}" @selected($selected === $nationality['label'])>{{ $nationality['label'] }}</option>
        @endforeach
    </select>
</div>
@if ($hasError)
    <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
@endif
