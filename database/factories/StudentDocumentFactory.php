<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'type' => fake()->randomElement(['birth_cert', 'transcript', 'photo', 'id_card']),
            'original_name' => fake()->word().'.pdf',
            'path' => 'student-documents/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 1048576),
            'uploaded_by' => User::factory()->state(['role' => 'admin']),
        ];
    }
}
