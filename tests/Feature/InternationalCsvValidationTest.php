<?php

namespace Tests\Feature;

use App\Services\BulkCsvImportService;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InternationalCsvValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_import_template_contains_all_required_and_optional_fields(): void
    {
        $headers = app(BulkCsvImportService::class)->templateHeaders('students');

        $this->assertSame([
            'name', 'email', 'guardian_id', 'class_id', 'document_type', 'issuing_country', 'document_number',
            'program_id', 'academic_year_id', 'admission_no', 'admission_date', 'dob', 'gender', 'phone',
            'phone_region', 'status',
        ], $headers);
    }

    public function test_csv_templates_can_be_downloaded_and_common_headers_are_recognized(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create([
            'name' => 'Import Admin',
            'email' => 'import-template-admin@example.test',
            'password' => 'password',
            'role' => 'super-admin',
        ]);
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.imports.template', ['entity' => 'students']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'name,email,guardian_id,class_id,document_type,issuing_country,document_number',
            $response->streamedContent()
        );
        $this->actingAs($admin)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Download CSV template')
            ->assertSee('export report');

        $export = UploadedFile::fake()->createWithContent(
            'student-register.csv',
            "Admission No,Student,Email,Class,Guardian,Guardian Phone,Student Phone,Date of Birth\n".
            "STU-001,Aarav Perera,aarav@example.test,Grade 7,Amara Perera,0771234567,0771234568,2014-02-03\n"
        );
        $this->actingAs($admin)
            ->post(route('admin.imports.store'), [
                'entity' => 'students',
                'mode' => 'dry_run',
                'file' => $export,
            ])
            ->assertOk()
            ->assertSee('File not validated')
            ->assertSee('no rows were checked')
            ->assertSee('student register export does not include guardian IDs')
            ->assertSee(route('admin.imports.template', ['entity' => 'students']))
            ->assertDontSee('0 valid · 0 invalid');

        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "Student,Email Address,Parent ID,School Class ID,ID Type,Country Code,ID Number\n".
            "Aarav Perera,aarav@example.test,1,1,passport,LK,AB123456\n"
        );
        $result = app(BulkCsvImportService::class)->inspect('students', $file);

        $this->assertSame([], $result['file_errors']);
        $this->assertSame(1, $result['invalid_count']);
    }

    public function test_guardian_csv_import_validates_international_names_and_country_phone_numbers(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'guardians.csv',
            "name,email,phone,phone_region\n".
            "Amara Perera,amara.perera@harbourview.test,077 123 4567,LK\n".
            "Amara2 Perera,amara2.perera@harbourview.test,077 123 4567,LK\n".
            "Nadia Hassan,nadia.hassan@harbourview.test,077 12,LK\n"
        );

        $result = app(BulkCsvImportService::class)->inspect('guardians', $file);

        $this->assertFalse($result['can_import']);
        $this->assertSame(1, $result['valid_count']);
        $this->assertSame(2, $result['invalid_count']);
        $this->assertStringContainsString('name:', implode(' ', $result['rows'][1]['errors']));
        $this->assertStringContainsString('phone:', implode(' ', $result['rows'][2]['errors']));
    }

    public function test_valid_guardian_csv_phone_is_normalized_when_imported(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'guardians.csv',
            "name,email,phone,phone_region\n".
            "Amara Perera,amara.perera@harbourview.test,077 123 4567,LK\n"
        );
        $importer = app(BulkCsvImportService::class);
        $result = $importer->inspect('guardians', $file);

        $this->assertTrue($result['can_import']);
        $this->assertSame(1, $importer->import('guardians', $result['valid_rows']));
        $this->assertDatabaseHas('guardians', ['phone' => '+94771234567']);
    }

    public function test_student_csv_import_requires_and_saves_document_identifiers(): void
    {
        $department = Department::create(['name' => 'International Studies', 'code' => 'INTL']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'IB Middle Years Programme', 'code' => 'IBMYP']);
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30']);
        $class = SchoolClass::create([
            'name' => 'Grade 7',
            'section' => 'A',
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);
        $guardianUser = User::create(['name' => 'Amara Perera', 'email' => 'amara.perera@harbourview.test', 'password' => 'password', 'role' => 'parent']);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '+94771234567']);
        $csv = "name,email,guardian_id,class_id,document_type,issuing_country,document_number\n".
            "Aarav Perera,aarav.perera@harbourview.test,{$guardian->id},{$class->id},passport,LK,AB123456\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);
        $importer = app(BulkCsvImportService::class);
        $result = $importer->inspect('students', $file);

        $this->assertTrue($result['can_import']);
        $this->assertSame(1, $importer->import('students', $result['valid_rows']));
        $this->assertDatabaseHas('students', [
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'AB123456',
            'national_id' => 'AB123456',
        ]);
    }

    public function test_student_csv_import_rejects_missing_document_columns(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "name,email,guardian_id,class_id\nAarav Perera,aarav.perera@harbourview.test,1,1\n"
        );

        $result = app(BulkCsvImportService::class)->inspect('students', $file);

        $this->assertFalse($result['can_import']);
        $this->assertStringContainsString('document_type', implode(' ', $result['file_errors']));
        $this->assertStringContainsString('issuing_country', implode(' ', $result['file_errors']));
        $this->assertStringContainsString('document_number', implode(' ', $result['file_errors']));
    }
}
