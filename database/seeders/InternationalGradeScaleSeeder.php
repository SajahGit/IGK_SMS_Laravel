<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use Illuminate\Database\Seeder;

class InternationalGradeScaleSeeder extends Seeder
{
    private const SCALES = [
        'Cambridge Achievement' => [
            [0, 39.99, 'Emerging', 1, false],
            [40, 59.99, 'Developing', 2, false],
            [60, 79.99, 'Secure', 3, true],
            [80, 100, 'Exceeding', 4, true],
        ],
        'IB 1-7' => [
            [0, 9.99, '1', 1, false],
            [10, 19.99, '2', 2, false],
            [20, 39.99, '3', 3, false],
            [40, 54.99, '4', 4, true],
            [55, 69.99, '5', 5, true],
            [70, 84.99, '6', 6, true],
            [85, 100, '7', 7, true],
        ],
        'IGCSE A*-G' => [
            [0, 19.99, 'U', 0, false],
            [20, 29.99, 'G', 1, false],
            [30, 39.99, 'F', 1.5, false],
            [40, 49.99, 'E', 2, false],
            [50, 59.99, 'D', 2.5, false],
            [60, 69.99, 'C', 3, true],
            [70, 79.99, 'B', 3.5, true],
            [80, 89.99, 'A', 4, true],
            [90, 100, 'A*', 4, true],
        ],
        'Edexcel International GCSE 9-1' => [
            [0, 9.99, 'U', 0, false],
            [10, 19.99, '1', 1, false],
            [20, 29.99, '2', 2, false],
            [30, 39.99, '3', 3, false],
            [40, 49.99, '4', 4, true],
            [50, 59.99, '5', 5, true],
            [60, 69.99, '6', 6, true],
            [70, 79.99, '7', 7, true],
            [80, 89.99, '8', 8, true],
            [90, 100, '9', 9, true],
        ],
        'A-Level A*-E' => [
            [0, 39.99, 'U', 0, false],
            [40, 49.99, 'E', 1, true],
            [50, 59.99, 'D', 2, true],
            [60, 69.99, 'C', 3, true],
            [70, 79.99, 'B', 3.5, true],
            [80, 89.99, 'A', 4, true],
            [90, 100, 'A*', 4, true],
        ],
        'American GPA' => [
            [0, 59.99, 'F', 0, false],
            [60, 69.99, 'D', 1, true],
            [70, 79.99, 'C', 2, true],
            [80, 89.99, 'B', 3, true],
            [90, 100, 'A', 4, true],
        ],
    ];

    public function run(): void
    {
        foreach (self::SCALES as $name => $ranges) {
            $scale = GradeScale::firstOrCreate(['name' => $name], ['is_default' => false]);

            foreach ($ranges as [$minimum, $maximum, $grade, $points, $isPass]) {
                $scale->items()->updateOrCreate(
                    ['grade' => $grade],
                    [
                        'min_percent' => $minimum,
                        'max_percent' => $maximum,
                        'grade_point' => $points,
                        'is_pass' => $isPass,
                    ]
                );
            }
        }
    }
}
