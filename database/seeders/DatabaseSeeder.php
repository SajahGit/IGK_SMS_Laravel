<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The admin account always exists
        User::updateOrCreate(
            ['email' => 'admin@school.test'],
            ['name' => 'System Admin', 'password' => config('school.default_password'), 'role' => 'admin', 'is_active' => true]
        );

        // Each person owns one demo seeder. A missing one is skipped,
        // so everybody can run "migrate:fresh --seed" before the final merge.
        foreach ([
            AdminDemoSeeder::class,    // Alass
            TeacherDemoSeeder::class,  // Sumaiya
            StudentDemoSeeder::class,  // Rajeeya
        ] as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
