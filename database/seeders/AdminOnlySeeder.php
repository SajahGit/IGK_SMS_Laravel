<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Clean start: creates ONLY the admin account (no demo teachers, students or exams).
 * Run with:  php artisan migrate:fresh --seed --seeder=AdminOnlySeeder
 */
class AdminOnlySeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@school.test'],
            ['name' => 'System Admin', 'password' => config('school.default_password'), 'role' => 'admin', 'is_active' => true]
        );
    }
}
