<?php

namespace App\Support;

use App\Models\Setting;

class InstitutionDetails
{
    public static function values(): array
    {
        return [
            'name' => Setting::get('institution_name', config('app.name')),
            'address' => Setting::get('institution_address'),
            'phone' => Setting::get('institution_phone'),
            'email' => Setting::get('institution_email'),
        ];
    }
}
