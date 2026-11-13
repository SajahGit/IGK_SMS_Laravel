<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(ProductionSeeder::class);

            return;
        }

        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Database seeding is restricted to local, testing, or production environments.');
        }

        $this->call(FullSampleDataSeeder::class);
    }
}
