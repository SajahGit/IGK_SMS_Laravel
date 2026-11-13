<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('Enter a valid name.');

            return;
        }

        $name = trim($value);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100
            || preg_match('/\A[\p{L}\p{M}]+(?:(?:[ \'-]|\.[ ]?)[\p{L}\p{M}]+)*\.?\z/u', $name) !== 1) {
            $fail('Enter 2 to 100 letters; spaces, periods, hyphens, and apostrophes are allowed.');
        }
    }
}
