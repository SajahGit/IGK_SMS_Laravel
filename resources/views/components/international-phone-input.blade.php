@props([
    'name',
    'value' => '',
    'required' => false,
])
@php
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $phoneHasError = ($errors ?? null)?->has($name) ?? false;
    $regions = collect($phoneUtil->getSupportedRegions())
        ->reject(fn (string $region) => $region === '001')
        ->mapWithKeys(function (string $region) use ($phoneUtil): array {
            $metadata = $phoneUtil->getMetadataForRegion($region);
            $callingCode = $phoneUtil->getCountryCodeForRegion($region);
            $lengths = collect($metadata?->getGeneralDesc()->getPossibleLength() ?? [])
                ->filter(fn (int $length) => $length > 0 && $length <= 15 - strlen((string) $callingCode))
                ->unique()
                ->sort()
                ->values()
                ->all();
            if ($lengths === []) {
                $lengths = [15 - strlen((string) $callingCode)];
            }

            return [$region => [
                'calling_code' => $callingCode,
                'national_lengths' => $lengths,
                'national_prefix' => $metadata?->getNationalPrefix() === '0' ? '0' : '',
            ]];
        })
        ->sortKeys();
    $phoneValue = old($name, $value);
    $selectedRegion = strtoupper((string) old($name.'_region', config('school.default_phone_region', 'LK')));
    $selectedRegion = isset($regions[$selectedRegion]) ? $selectedRegion : 'LK';
    $nationalNumber = (string) ($phoneValue ?? '');

    if ($nationalNumber !== '') {
        try {
            $internationalFormat = str_starts_with(trim($nationalNumber), '+');
            $regionWasSelected = old($name.'_region') !== null;
            $parsedNumber = $phoneUtil->parse($nationalNumber, $selectedRegion);
            if ($phoneUtil->isValidNumber($parsedNumber)) {
                $numberRegion = $phoneUtil->getRegionCodeForNumber($parsedNumber);
                if ((! $regionWasSelected || $internationalFormat) && isset($regions[$numberRegion])) {
                    $selectedRegion = $numberRegion;
                }
                $nationalNumber = $phoneUtil->getNationalSignificantNumber($parsedNumber);
            }
        } catch (\libphonenumber\NumberParseException) {
        }
    }
    $selectedCountry = $regions[$selectedRegion];
    $selectedMaxLength = max($selectedCountry['national_lengths']);
    $selectedMaxInputLength = $selectedMaxLength + strlen($selectedCountry['national_prefix']);
@endphp
<div data-international-phone class="input-group">
    <div class="dropdown phone-country-picker">
        <button
            type="button"
            class="btn btn-outline-secondary dropdown-toggle phone-country-toggle"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-haspopup="listbox"
            aria-label="Country calling code, {{ $selectedRegion }}, +{{ $selectedCountry['calling_code'] }}"
            data-phone-toggle
            data-phone-lengths="{{ implode(',', $selectedCountry['national_lengths']) }}"
            data-national-prefix="{{ $selectedCountry['national_prefix'] }}"
        >
            <span class="phone-flag fi fi-{{ strtolower($selectedRegion) }}" aria-hidden="true" data-phone-selected-flag></span>
            <span data-phone-selected-code>+{{ $selectedCountry['calling_code'] }}</span>
        </button>
        <div class="dropdown-menu phone-country-menu" role="listbox">
            <div class="p-2">
                <input type="search" class="form-control form-control-sm" placeholder="Search country code" aria-label="Search phone country codes" data-phone-search data-no-auto-filter>
            </div>
            @foreach ($regions as $region => $country)
                <button
                    type="button"
                    class="dropdown-item phone-country-option"
                    role="option"
                    aria-selected="{{ $selectedRegion === $region ? 'true' : 'false' }}"
                    data-phone-option
                    data-region="{{ $region }}"
                    data-search="{{ strtolower($region.' +'.$country['calling_code']) }}"
                    data-calling-code="{{ $country['calling_code'] }}"
                    data-phone-lengths="{{ implode(',', $country['national_lengths']) }}"
                    data-national-prefix="{{ $country['national_prefix'] }}"
                >
                    <span class="phone-flag fi fi-{{ strtolower($region) }}" aria-hidden="true"></span>
                    <span>+{{ $country['calling_code'] }} ({{ $region }})</span>
                </button>
            @endforeach
        </div>
    </div>
    <select class="visually-hidden" name="{{ $name }}_region" aria-hidden="true" tabindex="-1" data-phone-region>
        @foreach ($regions as $region => $country)
            <option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $region }}</option>
        @endforeach
    </select>
    <input
        {{ $attributes->class(['form-control', 'is-invalid' => $phoneHasError]) }}
        id="{{ $attributes->get('id', $name) }}"
        name="{{ $name }}"
        type="tel"
        inputmode="numeric"
        autocomplete="tel-national"
        aria-label="Phone number"
        data-phone-number
        data-phone-lengths="{{ implode(',', $selectedCountry['national_lengths']) }}"
        pattern="[0-9]+"
        data-phone-max-length="{{ $selectedMaxLength }}"
        data-national-prefix="{{ $selectedCountry['national_prefix'] }}"
        maxlength="{{ $selectedMaxInputLength }}"
        value="{{ $nationalNumber }}"
        @required($required)
    >
</div>
<div class="form-text" data-phone-length-help>
    Valid national number length{{ count($selectedCountry['national_lengths']) === 1 ? '' : 's' }}:
    {{ implode(', ', $selectedCountry['national_lengths']) }} digits.
    @if ($selectedCountry['national_prefix'] !== '')
        A leading {{ $selectedCountry['national_prefix'] }} is removed when entered.
    @endif
</div>
@if ($phoneHasError)
    <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
@endif
