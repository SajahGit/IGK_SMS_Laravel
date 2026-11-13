@props([
    'name',
    'value' => '',
    'required' => false,
])
@php
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $countryNames = [
        'AC' => 'Ascension Island',
        'AE' => 'United Arab Emirates',
        'AU' => 'Australia',
        'CN' => 'China',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'IN' => 'India',
        'JP' => 'Japan',
        'KE' => 'Kenya',
        'LK' => 'Sri Lanka',
        'SG' => 'Singapore',
        'TA' => 'Tristan da Cunha',
        'US' => 'United States',
        'ZA' => 'South Africa',
    ];
    $regions = collect($phoneUtil->getSupportedRegions())
        ->reject(fn (string $region) => $region === '001')
        ->mapWithKeys(fn (string $region) => [
            $region => [
                'calling_code' => $phoneUtil->getCountryCodeForRegion($region),
                'name' => $countryNames[$region] ?? $region,
            ],
        ])
        ->sortKeys();
    $selectedRegion = strtoupper((string) old($name, $value));
    if (! isset($regions[$selectedRegion])) {
        $selectedRegion = '';
    }
    $selectedCountry = $regions[$selectedRegion] ?? null;
    $hasError = ($errors ?? null)?->has($name) ?? false;
@endphp
<div class="dropdown international-country-picker" data-international-country>
    <button
        type="button"
        class="btn btn-outline-secondary dropdown-toggle international-country-toggle{{ $hasError ? ' is-invalid' : '' }}"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-haspopup="listbox"
        aria-label="{{ $selectedCountry ? 'Issuing country: '.$selectedCountry['name'] : 'Select issuing country' }}"
        aria-required="{{ $required ? 'true' : 'false' }}"
        data-country-toggle
    >
        @if ($selectedRegion !== '')
            <span class="phone-flag fi fi-{{ strtolower($selectedRegion) }}" aria-hidden="true" data-country-flag></span>
        @else
            <span class="phone-flag bi bi-globe" aria-hidden="true" data-country-flag></span>
        @endif
        <span data-country-name>{{ $selectedCountry['name'] ?? 'Select country' }}</span>
    </button>
    <div class="dropdown-menu international-country-menu" role="listbox">
        <div class="p-2">
            <input type="search" class="form-control form-control-sm" placeholder="Search countries" aria-label="Search issuing countries" data-country-search data-no-auto-filter>
        </div>
        @foreach ($regions as $region => $country)
            <button
                type="button"
                class="dropdown-item international-country-option"
                role="option"
                aria-selected="{{ $selectedRegion === $region ? 'true' : 'false' }}"
                data-country-option
                data-region="{{ $region }}"
                data-name="{{ $country['name'] }}"
            >
                <span class="phone-flag fi fi-{{ strtolower($region) }}" aria-hidden="true"></span>
                <span class="flex-grow-1">{{ $country['name'] }}</span>
                <span class="small text-muted">+{{ $country['calling_code'] }}</span>
            </button>
        @endforeach
    </div>
    <select class="visually-hidden" name="{{ $name }}" aria-hidden="true" tabindex="-1" data-country-value>
        <option value="">Select country</option>
        @foreach ($regions as $region => $country)
            <option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $country['name'] }}</option>
        @endforeach
    </select>
</div>
@if ($hasError)
    <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
@endif
