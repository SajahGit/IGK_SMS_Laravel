<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidStudentDocument implements ValidationRule
{
    private const NATIONAL_ID_PATTERNS = [
        'LK' => '/\A(?:[0-9]{9}[VX]|[0-9]{12})\z/i',
        'IN' => '/\A(?:[0-9]{12}|[A-Z]{5}[0-9]{4}[A-Z])\z/i',
        'GB' => '/\A[A-CEGHJ-PR-TW-Z]{2}[0-9]{6}[A-D]\z/i',
        'US' => '/\A[0-9]{9}\z/',
        'AE' => '/\A[0-9]{15}\z/',
        'FR' => '/\A[A-Z0-9]{12}\z/i',
        'KE' => '/\A[0-9]{7,8}\z/',
        'CN' => '/\A(?:[0-9]{15}|[0-9]{17}[0-9X])\z/i',
        'JP' => '/\A[0-9]{12}\z/',
        'SG' => '/\A[STFGM][0-9]{7}[A-Z]\z/i',
        'ZA' => '/\A[0-9]{13}\z/',
    ];

    public function __construct(
        private readonly mixed $documentType,
        private readonly mixed $issuingCountry
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! is_string($this->documentType) || ! is_string($this->issuingCountry)) {
            return;
        }

        $type = strtolower($this->documentType);
        $country = strtoupper($this->issuingCountry);
        $number = strtoupper(trim($value));

        if (! in_array($type, ['national_id', 'passport'], true)) {
            $fail('Choose National ID or Passport.');

            return;
        }

        if (preg_match('/\A[A-Z]{2}\z/', $country) !== 1) {
            $fail('Choose a valid issuing country.');

            return;
        }

        if ($type === 'passport') {
            if (preg_match('/\A[A-Z0-9]{6,9}\z/', $number) !== 1) {
                $fail('Passport numbers must contain 6 to 9 letters or digits.');
            }

            return;
        }

        if ($country === 'AU') {
            $fail('Australia does not issue a universal national ID. Select Passport or the issuing country of another accepted document.');

            return;
        }

        $pattern = self::NATIONAL_ID_PATTERNS[$country] ?? '/\A[A-Z0-9-]{5,20}\z/i';
        if (preg_match($pattern, $number) !== 1) {
            $fail('Enter a National ID in the format used by the selected issuing country.');
        }
    }
}
