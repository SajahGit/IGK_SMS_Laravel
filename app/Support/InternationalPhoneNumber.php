<?php

namespace App\Support;

use Illuminate\Http\Request;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class InternationalPhoneNumber
{
    public static function normalizeRequest(Request $request, string $field = 'phone'): void
    {
        if (! $request->has($field)) {
            return;
        }

        $phone = $request->input($field);
        if (! is_string($phone)) {
            return;
        }

        $region = $request->input($field.'_region');
        $request->merge([
            $field => self::normalize($phone, is_string($region) ? $region : null),
        ]);
    }

    public static function normalize(string $phone, ?string $region = null): string
    {
        $phone = trim($phone);
        if (preg_match('/\A\+?[0-9]+\z/', $phone) !== 1) {
            return $phone;
        }

        $region = str_starts_with($phone, '+')
            ? null
            : ($region !== null && $region !== ''
            ? strtoupper($region)
            : config('school.default_phone_region', 'LK'));
        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse($phone, $region);
            if ($util->isValidNumber($number)) {
                return $util->format($number, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException) {
            // Preserve invalid input so field validation can report it on phone.
        }

        return $phone;
    }
}
