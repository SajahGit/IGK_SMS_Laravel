<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use App\Services\GradeScaleService;
use Illuminate\Database\Seeder;

class GradeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InternationalGradeScaleSeeder::class);

        $defaultScale = GradeScale::firstOrCreate(
            ['name' => 'IB 1-7'],
            ['is_default' => true]
        );
        GradeScale::query()
            ->where('is_default', true)
            ->whereKeyNot($defaultScale->id)
            ->update(['is_default' => false]);
        if (! $defaultScale->is_default) {
            $defaultScale->update(['is_default' => true]);
        }

        GradeScaleService::clearCache();
    }
}
