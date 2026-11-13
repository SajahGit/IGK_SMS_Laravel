@props([
    'name',
    'guardians',
    'selected' => [],
    'multiple' => false,
    'required' => false,
    'allowNone' => false,
])
@php
    $selectedIds = collect(is_array($selected) ? $selected : [$selected])
        ->filter(fn ($id) => $id !== null && $id !== '')
        ->map(fn ($id) => (string) $id)
        ->all();
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $errors->has($errorKey);
@endphp
<div data-guardian-selector data-multiple="{{ $multiple ? 'true' : 'false' }}">
    <div class="input-group guardian-selector-search">
        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
        <input type="search" class="form-control" placeholder="Search guardians" aria-label="Search guardians" data-guardian-search>
    </div>
    <div class="guardian-selector-options border rounded p-2{{ $hasError ? ' border-danger' : '' }}" role="group" aria-label="{{ $multiple ? 'Select guardians' : 'Select a guardian' }}">
        @if ($allowNone && ! $multiple)
            <label class="guardian-selector-option" data-guardian-option data-name="No guardian linked" data-search="no guardian linked">
                <input class="form-check-input m-0" type="radio" name="{{ $name }}" value="" @checked($selectedIds === [])>
                <i class="bi bi-person" aria-hidden="true"></i>
                <span>No guardian linked</span>
            </label>
        @endif
        @foreach ($guardians as $guardian)
            @php
                $region = null;
                if ($guardian->phone) {
                    try {
                        $number = $phoneUtil->parse($guardian->phone, str_starts_with($guardian->phone, '+') ? null : config('school.default_phone_region', 'LK'));
                        $candidateRegion = $phoneUtil->getRegionCodeForNumber($number);
                        $region = preg_match('/\A[A-Z]{2}\z/', (string) $candidateRegion) === 1 ? $candidateRegion : null;
                    } catch (\libphonenumber\NumberParseException) {
                    }
                }
                $isSelected = in_array((string) $guardian->id, $selectedIds, true);
                $searchText = mb_strtolower($guardian->user->name.' '.$guardian->phone);
            @endphp
            <label class="guardian-selector-option" data-guardian-option data-name="{{ $guardian->user->name }}" data-search="{{ $searchText }}">
                <input
                    class="form-check-input m-0"
                    type="{{ $multiple ? 'checkbox' : 'radio' }}"
                    name="{{ $name }}"
                    value="{{ $guardian->id }}"
                    @checked($isSelected)
                    @required($required && ! $multiple)
                >
                @if ($region)
                    <span class="phone-flag fi fi-{{ strtolower($region) }}" aria-hidden="true"></span>
                @else
                    <i class="bi bi-globe phone-flag" aria-hidden="true"></i>
                @endif
                <span class="flex-grow-1 min-w-0">
                    <span class="d-block text-truncate">{{ $guardian->user->name }}</span>
                    @if ($guardian->phone)
                        <span class="guardian-selector-phone">{{ $guardian->phone }}</span>
                    @endif
                </span>
            </label>
        @endforeach
        @if ($guardians->isEmpty())
            <div class="text-muted small p-2">No guardians are available.</div>
        @endif
    </div>
    <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
        <div class="form-text mb-0">{{ $multiple ? 'Select one or more guardians.' : 'Choose the primary contact.' }}</div>
        <span class="small text-muted text-nowrap" data-guardian-count></span>
    </div>
    @if ($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($errorKey) }}</div>
    @endif
</div>
