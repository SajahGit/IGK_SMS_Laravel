<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'name' => 'Grade '.fake()->numberBetween(1, 13),
            'section' => strtoupper(fake()->unique()->randomLetter()),
            'capacity' => 40,
            'program_id' => Program::factory(),
            'academic_year_id' => AcademicYear::factory(),
        ];
    }
}
