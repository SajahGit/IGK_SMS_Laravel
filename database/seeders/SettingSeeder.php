<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'institution_name', 'value' => 'Architectured School Management System', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_address', 'value' => '123 Education Road, Academic City', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_phone', 'value' => '+1-555-0100', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'institution_email', 'value' => 'info@school.test', 'type' => 'string', 'group' => 'institution'],
            ['key' => 'attendance_edit_window_hours', 'value' => '48', 'type' => 'integer', 'group' => 'academic'],
            ['key' => 'default_credit_hours', 'value' => '3', 'type' => 'integer', 'group' => 'academic'],
            ['key' => 'pass_mark_percentage', 'value' => '40', 'type' => 'integer', 'group' => 'academic'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
