<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrarAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_open_its_dashboard_and_read_academic_but_not_financial_reports(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $registrar = User::create([
            'name' => 'Registrar',
            'email' => 'registrar@example.test',
            'password' => 'password',
            'role' => 'registrar',
        ]);
        $registrar->syncRoles(['Registrar']);

        SchoolClass::create(['name' => 'Grade 8', 'section' => 'A']);

        $this->actingAs($registrar)
            ->get(route('registrar.dashboard'))
            ->assertOk()
            ->assertSee('Registrar Dashboard');

        $this->get(route('reports.enrollment'))
            ->assertOk();

        $this->get(route('reports.fee-collection'))
            ->assertForbidden();
        $this->get(route('reports.student-ledger'))
            ->assertForbidden();
    }

    public function test_registrar_gets_scoped_read_access_but_not_role_or_finance_write_access(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $registrar = User::create([
            'name' => 'Registrar',
            'email' => 'registrar@example.test',
            'password' => 'password',
            'role' => 'registrar',
        ]);
        $registrar->syncRoles(['Registrar']);

        $this->actingAs($registrar)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->get(route('admin.students.index'))->assertOk();
        $this->get(route('admin.departments.create'))->assertForbidden();
        $this->get(route('admin.programs.create'))->assertForbidden();
        $this->get(route('admin.curricula.create'))->assertForbidden();
        $this->get(route('admin.exams.create'))->assertOk();
        $this->post(route('admin.departments.store'), ['name' => 'Languages', 'code' => 'LANG'])
            ->assertForbidden();
        $this->assertDatabaseMissing('departments', ['code' => 'LANG']);
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->get(route('accountant.invoices.create'))->assertForbidden();
        $this->get(route('accountant.payments.create'))
            ->assertForbidden();
    }

    public function test_registrar_can_open_create_class_form_with_dropdowns_and_save_class(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $branch = Branch::create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'is_main' => true,
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $dept = Department::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Physical Science',
            'code' => 'PS',
            'is_active' => true,
        ]);

        $registrar = User::create([
            'name' => 'Registrar Officer',
            'email' => 'registrar.class@example.test',
            'password' => 'password',
            'role' => 'registrar',
            'branch_id' => $branch->id,
        ]);
        $registrar->syncRoles(['Registrar']);

        $response = $this->actingAs($registrar)->get(route('admin.classes.create'));
        $response->assertOk();
        $response->assertSee('Add class');
        $response->assertSee('Class name (e.g. Grade 10)');
        $response->assertSee('Campus / Branch');
        $response->assertSee('Physical Science');
        $response->assertSee('2026-2027');
        $response->assertSee('Main Campus (MAIN)');

        // Save class
        $postResponse = $this->actingAs($registrar)->post(route('admin.classes.store'), [
            'name' => 'Grade 11',
            'section' => 'A',
            'capacity' => 35,
            'branch_id' => $branch->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);

        $postResponse->assertRedirect(route('admin.classes.index'));
        $postResponse->assertSessionHas('success', 'Class created successfully.');

        $this->assertDatabaseHas('classes', [
            'name' => 'Grade 11',
            'section' => 'A',
            'capacity' => 35,
            'branch_id' => $branch->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);

        $schoolClass = SchoolClass::where('name', 'Grade 11')->where('section', 'A')->firstOrFail();
        $editResponse = $this->get(route('admin.classes.edit', $schoolClass));
        $editResponse->assertOk();
        $editResponse->assertSee('Grade 11');
        $editResponse->assertSee('Update Class');
    }
}
