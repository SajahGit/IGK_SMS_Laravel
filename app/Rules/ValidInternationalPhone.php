<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ValidInternationalPhone implements ValidationRule
{
    public function __construct(private readonly mixed $region = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if ($this->region !== null && ! is_string($this->region)) {
            $fail('Select a valid country calling code.');

            return;
        }

        if (preg_match('/\A\+?[0-9]+\z/', $value) !== 1) {
            $fail('Enter digits only for the phone number.');

            return;
        }

        if (str_starts_with($value, '+')) {
            $digitCount = strlen(substr($value, 1));
            if ($digitCount < 7 || $digitCount > 15) {
                $fail('International phone numbers must contain 7 to 15 digits, excluding the leading +.');

                return;
            }
        }

        $region = $this->region
            ? strtoupper($this->region)
            : (str_starts_with($value, '+') ? null : strtoupper(config('school.default_phone_region', 'LK')));
        $util = PhoneNumberUtil::getInstance();

        if ($region !== null && ! in_array($region, $util->getSupportedRegions(), true)) {
            $fail('Select a valid country calling code.');

            return;
        }

        try {
            $number = $util->parse($value, $region);
            $isValid = $region === null
                ? $util->isValidNumber($number)
                : $util->isValidNumberForRegion($number, $region);
        } catch (NumberParseException) {
            $isValid = false;
        }

        if (! $isValid) {
            $fail('Enter a valid phone number for the selected country, including its correct number length.');
        }
    }
}
