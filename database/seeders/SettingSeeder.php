<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'institution_name', 'value' => 'IGK International School', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_address', 'value' => 'Colombo, Sri Lanka', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_phone', 'value' => '+94 11 234 5600', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_email', 'value' => 'admissions@igk-international.example', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'default_currency', 'value' => config('school.default_currency', 'LKR'), 'type' => 'string', 'group' => 'finance'],
            ['key' => 'attendance_edit_window_hours', 'value' => '48', 'type' => 'integer', 'group' => 'academic'],
            ['key' => 'default_credit_hours', 'value' => '3', 'type' => 'integer', 'group' => 'academic'],
            ['key' => 'pass_mark_percentage', 'value' => '40', 'type' => 'integer', 'group' => 'academic'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
