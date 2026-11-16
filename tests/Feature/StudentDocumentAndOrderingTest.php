<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentDocumentAndOrderingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private SchoolClass $class;
    private Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $this->branch = Branch::create([
            'name' => 'Main Campus',
            'code' => 'COL',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch_id' => $this->branch->id,
        ]);
        $this->admin->syncRoles(['Super Admin', 'Institution Admin']);

        $department = \App\Models\Department::create([
            'name' => 'General Education',
            'code' => 'GEN',
        ]);

        $program = \App\Models\Program::create([
            'name' => 'High School',
            'code' => 'HS',
            'department_id' => $department->id,
        ]);

        $academicYear = \App\Models\AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $this->class = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'capacity' => 40,
            'branch_id' => $this->branch->id,
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $guardianUser = User::factory()->create([
            'role' => 'parent',
            'branch_id' => $this->branch->id,
        ]);
        $guardianUser->syncRoles(['Guardian']);

        $this->guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'relationship' => 'Father',
        ]);
    }

    public function test_newly_enrolled_student_appears_at_the_very_top_of_the_students_list(): void
    {
        // Create older student
        $oldStudentUser = User::factory()->create(['name' => 'A Old Student', 'role' => 'student']);
        $oldStudent = Student::create([
            'user_id' => $oldStudentUser->id,
            'class_id' => $this->class->id,
            'branch_id' => $this->branch->id,
            'admission_no' => '2026-AMEREL-0001',
            'status' => 'active',
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '199512345678',
        ]);

        // Enroll new student via store endpoint
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Z Brand New Student',
            'email' => 'new.student@school.test',
            'password' => 'Password123!',
            'class_id' => $this->class->id,
            'branch_id' => $this->branch->id,
            'guardian_ids' => [$this->guardian->id],
            'issuing_country' => 'LK',
            'document_type' => 'national_id',
            'document_number' => '200512345678',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        // Visit students index page
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.students.index'));
        $indexResponse->assertOk();

        // The newly created student must appear on the page before the old student in table order
        $content = $indexResponse->getContent();
        $newPos = strpos($content, 'Z Brand New Student');
        $oldPos = strpos($content, 'A Old Student');

        $this->assertNotFalse($newPos, 'New student should be visible on page 1');
        $this->assertNotFalse($oldPos, 'Old student should be visible');
        $this->assertLessThan($oldPos, $newPos, 'New student must appear above older students in the table');
    }

    public function test_student_can_upload_documents_during_admission(): void
    {
        $dummyFile = UploadedFile::fake()->create('birth_certificate.pdf', 250, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'Tariq Document Student',
            'email' => 'tariq.doc@school.test',
            'password' => 'Password123!',
            'class_id' => $this->class->id,
            'branch_id' => $this->branch->id,
            'guardian_ids' => [$this->guardian->id],
            'issuing_country' => 'LK',
            'document_type' => 'national_id',
            'document_number' => '200312345678',
            'documents' => [$dummyFile],
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'tariq.doc@school.test'))->firstOrFail();

        $this->assertCount(1, $student->documents);
        $doc = $student->documents->first();
        $this->assertSame('birth_certificate.pdf', $doc->original_name);
        Storage::disk('local')->assertExists($doc->path);
    }

    public function test_document_can_be_uploaded_without_mandatory_type_dropdown(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'branch_id' => $this->branch->id,
            'admission_no' => '2026-COL-9999',
            'status' => 'active',
            'document_type' => 'national_id',
            'issuing_country' => 'LK',
            'document_number' => '199812345678',
        ]);

        $file = UploadedFile::fake()->create('passport_copy.png', 120, 'image/png');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.students.documents.upload', $student), [
                'document' => $file,
                // Notice 'type' is omitted completely
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('student_documents', [
            'student_id' => $student->id,
            'original_name' => 'passport_copy.png',
        ]);
    }
}
