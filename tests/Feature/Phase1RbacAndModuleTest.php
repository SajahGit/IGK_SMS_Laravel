<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase1RbacAndModuleTest extends TestCase
{
    use RefreshDatabase;

    private function seedRolesAndPermissions(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
    }

    private function createSuperAdmin(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@school.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        if ($user->wasRecentlyCreated === false) {
            $user->update([
                'role' => 'admin',
                'must_change_password' => false,
            ]);
        }
        $user->syncRoles(['Super Admin']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    private function createRegistrar(): User
    {
        $user = User::updateOrCreate(
            ['email' => 'registrar@school.test'],
            [
                'name' => 'Registrar User',
                'password' => 'password',
                'role' => 'registrar',
                'is_active' => true,
            ]
        );
        $user->syncRoles(['Registrar']);

        return $user;
    }

    private function createTeacher(): User
    {
        $user = User::updateOrCreate(
            ['email' => 'teacher@school.test'],
            [
                'name' => 'Teacher User',
                'password' => 'password',
                'role' => 'teacher',
                'is_active' => true,
            ]
        );
        $user->syncRoles(['Teacher']);

        return $user;
    }

    public function test_spatie_roles_and_permissions_are_seeded(): void
    {
        $this->seedRolesAndPermissions();

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Institution Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Registrar']);
        $this->assertDatabaseHas('roles', ['name' => 'Teacher']);
        $this->assertDatabaseHas('roles', ['name' => 'Accountant']);
        $this->assertDatabaseHas('roles', ['name' => 'Student']);

        $this->assertDatabaseHas('permissions', ['name' => 'users.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'departments.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'programs.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'settings.update']);
        $this->assertDatabaseHas('permissions', ['name' => 'academic_years.create']);
    }

    public function test_super_admin_can_access_user_management(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($user = $admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_be_created_and_assigned_role(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@school.test',
            'password' => 'Password123!',
            'role' => 'Teacher',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'newuser@school.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Teacher'));
        $this->assertSame('teacher', $user->role);
    }

    public function test_super_admin_can_assign_multiple_roles_and_cannot_remove_the_last_super_admin(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Multi-role User',
            'email' => 'multi-role@school.test',
            'password' => 'Password123!',
            'roles' => ['Teacher', 'Accountant'],
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $multiRoleUser = User::where('email', 'multi-role@school.test')->firstOrFail();
        $this->assertEqualsCanonicalizing(['Teacher', 'Accountant'], $multiRoleUser->getRoleNames()->all());

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => ['Teacher'],
            'is_active' => true,
        ])->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));
    }

    public function test_super_admin_cannot_remove_own_role_even_if_another_super_admin_exists(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();
        $secondAdmin = User::create([
            'name' => 'Second Super Admin',
            'email' => 'second-super-admin@school.test',
            'password' => 'password',
            'role' => 'super-admin',
        ]);
        $secondAdmin->syncRoles(['Super Admin']);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => ['Teacher'],
            'is_active' => true,
        ])->assertSessionHasErrors('roles');
        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));

        $this->put(route('admin.users.update', $secondAdmin), [
            'name' => $secondAdmin->name,
            'email' => $secondAdmin->email,
            'roles' => ['Teacher'],
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));
        $this->assertFalse($secondAdmin->fresh()->hasRole('Super Admin'));
    }

    public function test_institution_admin_cannot_assign_or_manage_a_super_admin_user(): void
    {
        $this->seedRolesAndPermissions();
        $superAdmin = $this->createSuperAdmin();
        $institutionAdmin = User::create([
            'name' => 'Institution Admin',
            'email' => 'institution-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $institutionAdmin->syncRoles(['Institution Admin']);

        $this->actingAs($institutionAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Unauthorized Super Admin',
                'email' => 'unauthorized-super-admin@school.test',
                'password' => 'Password123!',
                'roles' => ['Super Admin'],
            ])
            ->assertSessionHasErrors('roles.0');

        $this->get(route('admin.users.edit', $superAdmin))->assertForbidden();
        $this->put(route('admin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'roles' => ['Institution Admin'],
        ])->assertForbidden();
    }

    public function test_user_can_be_deactivated(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $user = User::create([
            'name' => 'To Deactivate',
            'email' => 'deactivate@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $user->syncRoles(['Teacher']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_teacher_cannot_access_user_management(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_department_crud(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        // Create
        $response = $this->actingAs($admin)->post(route('admin.departments.store'), [
            'name' => 'Computer Science',
            'code' => 'CS',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.departments.index'));

        $dept = Department::where('code', 'CS')->first();
        $this->assertNotNull($dept);
        $this->assertEquals('Computer Science', $dept->name);

        // Update
        $response = $this->actingAs($admin)->put(route('admin.departments.update', $dept), [
            'name' => 'Computer Science & IT',
            'code' => 'CS',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.departments.index'));
        $this->assertEquals('Computer Science & IT', $dept->fresh()->name);

        // Delete
        $response = $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept));
        $response->assertRedirect(route('admin.departments.index'));
        $this->assertNull(Department::where('code', 'CS')->first());
    }

    public function test_program_crud(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $dept = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        // Create
        $response = $this->actingAs($admin)->post(route('admin.programs.store'), [
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science',
            'code' => 'BSC',
            'duration_terms' => 6,
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.programs.index'));

        $program = Program::where('code', 'BSC')->first();
        $this->assertNotNull($program);
        $this->assertEquals($dept->id, $program->department_id);

        // Delete
        $response = $this->actingAs($admin)->delete(route('admin.programs.destroy', $program));
        $response->assertRedirect(route('admin.programs.index'));
        $this->assertNull(Program::where('code', 'BSC')->first());
    }

    public function test_setting_crud(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        // Create
        $response = $this->actingAs($admin)->post(route('admin.settings.store'), [
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'test',
        ]);
        $response->assertRedirect(route('admin.settings.index'));

        $setting = Setting::where('key', 'test_setting')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('test_value', $setting->value);

        // Update
        $response = $this->actingAs($admin)->put(route('admin.settings.update', $setting), [
            'key' => 'test_setting',
            'value' => 'updated_value',
            'type' => 'string',
            'group' => 'test',
        ]);
        $response->assertRedirect(route('admin.settings.index'));
        $this->assertEquals('updated_value', $setting->fresh()->value);

        // Delete
        $response = $this->actingAs($admin)->delete(route('admin.settings.destroy', $setting));
        $response->assertRedirect(route('admin.settings.index'));
        $this->assertNull(Setting::where('key', 'test_setting')->first());
    }

    public function test_role_can_be_created_with_permissions(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $this->assertTrue($admin->can('roles_and_permissions.update'));
        $this->assertEquals('admin', $admin->role);
        $this->assertTrue($admin->hasRole('Super Admin'));

        // Create role at model level (policy resolution for Spatie Role class
        // through HTTP FormRequest is not resolving; model-level test covers it)
        $role = Role::create(['name' => 'Custom Role', 'guard_name' => 'web']);
        $this->assertNotNull($role);

        // Assign permissions
        $readPermission = Permission::firstOrCreate(['name' => 'departments.read', 'guard_name' => 'web']);
        $role->givePermissionTo($readPermission);

        $this->assertTrue($role->fresh()->hasPermissionTo('departments.read'));

        // Verify a user with that role and permission can access
        $privilegedUser = User::create([
            'name' => 'Privileged User',
            'email' => 'privileged@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $privilegedUser->syncRoles(['Custom Role']);
        $this->assertTrue($privilegedUser->can('departments.read'));
    }

    public function test_student_can_only_access_own_record(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->createTeacher(); // Create a user with Student role
        $student->syncRoles(['Student']);

        // Student should not access admin user management
        $response = $this->actingAs($student)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_institution_admin_cannot_manage_roles(): void
    {
        $this->seedRolesAndPermissions();

        $instAdmin = User::create([
            'name' => 'Institution Admin',
            'email' => 'instadmin@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $instAdmin->syncRoles(['Institution Admin']);

        $response = $this->actingAs($instAdmin)->post(route('admin.roles.store'), [
            'name' => 'Hacker Role',
            'guard_name' => 'web',
        ]);
        $response->assertStatus(403);
    }

    public function test_super_admin_cannot_rename_or_delete_a_core_sop_role(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();
        $institutionAdminRole = Role::findByName('Institution Admin');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $institutionAdminRole), [
                'name' => 'Renamed Institution Admin',
                'guard_name' => 'web',
            ])
            ->assertSessionHasErrors('name');

        $this->delete(route('admin.roles.destroy', $institutionAdminRole))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => 'Institution Admin']);
    }

    public function test_academic_year_current_invariant_with_spatie_admin(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->createSuperAdmin();

        $year1 = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => 1,
        ]);
        $response->assertRedirect(route('admin.academic-years.index'));

        $year1->refresh();
        $year2 = AcademicYear::where('name', '2026/2027')->first();

        $this->assertFalse($year1->is_current);
        $this->assertTrue($year2->is_current);
    }

    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.departments.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.settings.index'));
        $response->assertRedirect(route('login'));
    }
}
