<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Support\AdmissionNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'class_id' => SchoolClass::factory(),
            'admission_no' => 'pending-'.fake()->unique()->uuid(),
            'admission_date' => today()->toDateString(),
            'dob' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['female', 'male']),
            'status' => 'active',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Student $student) {
            $schoolClass = SchoolClass::findOrFail($student->class_id);
            $student->program_id = $schoolClass->program_id;
            $student->academic_year_id = $schoolClass->academic_year_id;
            $student->admission_no = AdmissionNumberGenerator::next(
                (int) $schoolClass->program_id,
                (int) $schoolClass->academic_year_id
            );
        });
    }

    public function applicant(): static
    {
        return $this->state(fn () => ['status' => 'applicant']);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function graduated(): static
    {
        return $this->state(fn () => ['status' => 'graduated']);
    }

    public function withGuardian(): static
    {
        return $this->afterCreating(function (Student $student) {
            $guardian = $student->guardian_id
                ? Guardian::findOrFail($student->guardian_id)
                : Guardian::factory()->create();
            $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);
            if ($student->guardian_id !== $guardian->id) {
                $student->update(['guardian_id' => $guardian->id]);
            }
        });
    }

    public function withDocuments(int $count = 1): static
    {
        return $this->afterCreating(fn (Student $student) => StudentDocument::factory()
            ->count($count)
            ->create(['student_id' => $student->id]));
    }
}
