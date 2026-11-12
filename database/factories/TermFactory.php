<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $sequence = fake()->numberBetween(1, 3);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Term '.$sequence,
            'sequence' => $sequence,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_current' => false,
        ];
    }
}
