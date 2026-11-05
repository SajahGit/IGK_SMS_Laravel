<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegisterExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_filtered_student_register_as_csv(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $class = SchoolClass::create(['name' => 'Grade 8', 'section' => 'A']);
        $studentUser = User::create([
            'name' => 'Sam Student',
            'email' => 'sam@example.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM-008',
        ]);
        $otherStudent = User::create([
            'name' => '=1+1',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'student',
        ]);
        Student::create([
            'user_id' => $otherStudent->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM-009',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.students.export', ['class_id' => $class->id, 'q' => 'Sam']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('student-register.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('ADM-008', $response->streamedContent());
        $this->assertStringNotContainsString('=1+1', $response->streamedContent());

        $formulaResponse = $this->get(route('admin.students.export', ['class_id' => $class->id, 'q' => '=1+1']));
        $this->assertStringContainsString("'=1+1", $formulaResponse->streamedContent());
    }

    public function test_student_register_export_is_limited_to_ten_requests_per_minute(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'export-throttle@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($admin)->get(route('admin.students.export'))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.students.export'))->assertTooManyRequests();
    }
}
