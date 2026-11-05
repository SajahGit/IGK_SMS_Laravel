<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrarAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_open_its_dashboard_and_read_academic_and_financial_reports(): void
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
            ->assertOk();
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
        $this->get(route('admin.departments.create'))->assertOk();
        $this->get(route('admin.programs.create'))->assertOk();
        $this->get(route('admin.curricula.create'))->assertOk();
        $this->get(route('admin.exams.create'))->assertOk();
        $this->post(route('admin.departments.store'), ['name' => 'Languages', 'code' => 'LANG'])
            ->assertRedirect(route('admin.departments.index'));
        $this->assertDatabaseHas('departments', ['code' => 'LANG']);
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->get(route('accountant.invoices.create'))->assertForbidden();
        $this->get(route('accountant.payments.create'))
            ->assertForbidden();
    }
}
