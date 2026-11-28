<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Compact, clean sample dataset for local/testing development.
 * Usage: php artisan migrate:fresh --seed --seeder=CompactDemoSeeder
 */
class CompactDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Compact demo data may only be seeded in local or testing environments.');
        }

        $this->call([
            ProductionSeeder::class,
            AcademicStructureSeeder::class,
            LocalDemoCredentialsSeeder::class,
            LearningMaterialsDemoSeeder::class,
        ]);
    }
}
