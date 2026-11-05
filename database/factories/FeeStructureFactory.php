<?php

namespace Database\Factories;

use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 *
 * Unchanged default (class + term), so every existing test keeps working exactly as
 * before. SOP §15.1 / US-F1-exact program_id / academic_year_id are populated
 * automatically by FeeStructure::booted() from the class given here — nothing to
 * do in this factory for that. To build a program-wide structure directly (no
 * class_id) in a new test, create it with FeeStructure::create([...]) passing
 * program_id/academic_year_id and no class_id — a factory state is not provided
 * because Program/AcademicYear have no factories of their own in this project yet.
 */
class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'class_id' => fn () => SchoolClass::create(['name' => 'Grade '.fake()->numberBetween(1, 13), 'section' => fake()->randomLetter()])->id,
            'term_id' => null,
            'name' => fake()->randomElement(['Tuition', 'Lab fee', 'Sports fee', 'Library fee']),
            'amount' => '5000.00',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function amount(string $amount): static
    {
        return $this->state(fn () => ['amount' => $amount]);
    }
}
