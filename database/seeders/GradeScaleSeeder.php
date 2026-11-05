<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use App\Services\GradeScaleService;
use Illuminate\Database\Seeder;

class GradeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scale = GradeScale::where('is_default', true)->first()
            ?? GradeScale::firstOrCreate(
                ['name' => 'Standard Grade Scale'],
                ['is_default' => true]
            );
        GradeScale::whereKeyNot($scale->id)->update(['is_default' => false]);
        $scale->update(['is_default' => true]);

        if (! $scale->items()->exists()) {
            foreach ([
                ['min_percent' => 85, 'max_percent' => 100, 'grade' => 'A', 'grade_point' => 4, 'is_pass' => true],
                ['min_percent' => 75, 'max_percent' => 84.99, 'grade' => 'B', 'grade_point' => 3, 'is_pass' => true],
                ['min_percent' => 65, 'max_percent' => 74.99, 'grade' => 'C', 'grade_point' => 2, 'is_pass' => true],
                ['min_percent' => 50, 'max_percent' => 64.99, 'grade' => 'S', 'grade_point' => 1, 'is_pass' => true],
                ['min_percent' => 0, 'max_percent' => 49.99, 'grade' => 'F', 'grade_point' => 0, 'is_pass' => false],
            ] as $grade) {
                $scale->items()->create($grade);
            }
        }

        GradeScaleService::clearCache();
    }
}
