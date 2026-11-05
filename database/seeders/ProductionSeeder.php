<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Roles and permissions (M2 RBAC) - required for production authorization
        $this->call(RolePermissionSeeder::class);

        // Institution settings (non-demo configuration)
        $this->call(SettingSeeder::class);

        // Default grade scale (required for grading/GPA calculation)
        $this->call(GradeScaleSeeder::class);

        // ONE super admin account (must be rotated at handover)
        $this->call(SuperAdminSeeder::class);
    }
}
