<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleLoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function seedRolesAndPermissions(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
    }

    #[Test]
    public function all_sop_roles_exist_in_database(): void
    {
        $this->seedRolesAndPermissions();

        $expectedRoles = [
            'Super Admin',
            'Institution Admin',
            'Registrar',
            'Teacher',
            'Accountant',
            'Student',
            'Guardian',
            'Librarian',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
            $this->assertTrue(Role::where('name', $roleName)->exists());
        }

        $this->assertCount(8, Role::all());
    }

    #[Test]
    public function no_parent_spatie_role_exists(): void
    {
        $this->seedRolesAndPermissions();

        $this->assertFalse(Role::where('name', 'Parent')->exists());
    }

    #[Test]
    public function super_admin_has_all_permissions(): void
    {
        $this->seedRolesAndPermissions();

        $superAdmin = Role::where('name', 'Super Admin')->first();
        $totalPermissions = Permission::count();

        $this->assertEquals($totalPermissions, $superAdmin->permissions->count());
    }

    #[Test]
    public function institution_admin_has_report_permissions(): void
    {
        $this->seedRolesAndPermissions();

        $instAdmin = Role::where('name', 'Institution Admin')->first();

        $this->assertTrue($instAdmin->hasPermissionTo('reports.academic'));
        $this->assertTrue($instAdmin->hasPermissionTo('reports.attendance'));
        $this->assertTrue($instAdmin->hasPermissionTo('reports.financial'));
        $this->assertTrue($instAdmin->hasPermissionTo('reports.read'));

        // Institution Admin may inspect role definitions but cannot change them.
        $this->assertFalse($instAdmin->hasPermissionTo('roles_and_permissions.create'));
        $this->assertTrue($instAdmin->hasPermissionTo('roles_and_permissions.read'));
        $this->assertFalse($instAdmin->hasPermissionTo('roles_and_permissions.update'));
        $this->assertFalse($instAdmin->hasPermissionTo('roles_and_permissions.delete'));
    }

    #[Test]
    public function registrar_has_academic_permissions_only(): void
    {
        $this->seedRolesAndPermissions();

        $registrar = Role::where('name', 'Registrar')->first();

        $this->assertTrue($registrar->hasPermissionTo('reports.academic'));
        $this->assertTrue($registrar->hasPermissionTo('students.create'));
        $this->assertTrue($registrar->hasPermissionTo('results.publish'));

        // Registrar must NOT have finance write permissions
        $this->assertFalse($registrar->hasPermissionTo('invoices.create'));
        $this->assertFalse($registrar->hasPermissionTo('payments.create'));
        $this->assertFalse($registrar->hasPermissionTo('fee_structures.create'));

        // Registrar must NOT have attendance create (read/update only)
        $this->assertTrue($registrar->hasPermissionTo('attendance.read'));
        $this->assertTrue($registrar->hasPermissionTo('attendance.update'));
        $this->assertFalse($registrar->hasPermissionTo('attendance.create'));
    }

    #[Test]
    public function teacher_has_assigned_class_permissions_only(): void
    {
        $this->seedRolesAndPermissions();

        $teacher = Role::where('name', 'Teacher')->first();

        $this->assertTrue($teacher->hasPermissionTo('attendance.create'));
        $this->assertTrue($teacher->hasPermissionTo('marks.read'));
        $this->assertTrue($teacher->hasPermissionTo('reports.attendance'));

        // Teacher must NOT manage users or roles
        $this->assertFalse($teacher->hasPermissionTo('users.create'));
        $this->assertFalse($teacher->hasPermissionTo('roles_and_permissions.create'));

        // Teacher must NOT have finance permissions
        $this->assertFalse($teacher->hasPermissionTo('invoices.create'));
        $this->assertFalse($teacher->hasPermissionTo('payments.create'));
    }

    #[Test]
    public function permission_revocation_takes_effect_on_the_next_request(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = User::create([
            'name' => 'Permission Revocation Teacher',
            'email' => 'permission-revocation@school.test',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $teacher->syncRoles(['Teacher']);

        $this->actingAs($teacher)
            ->get(route('reports.enrollment'))
            ->assertOk();

        Role::findByName('Teacher')->revokePermissionTo('reports.academic');

        $this->get(route('reports.enrollment'))->assertForbidden();
    }

    #[Test]
    public function role_management_navigation_is_only_shown_to_super_admin(): void
    {
        $this->seedRolesAndPermissions();

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin-navigation@school.test',
            'password' => 'password',
            'role' => 'super-admin',
            'must_change_password' => false,
        ]);
        $superAdmin->syncRoles(['Super Admin']);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Roles & Permissions')
            ->assertSeeText('Users')
            ->assertSeeText('Enrollment Register')
            ->assertSeeText('Student Ledger')
            ->assertSeeText('Record Payment');

        $institutionAdmin = User::create([
            'name' => 'Institution Admin',
            'email' => 'institution-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $institutionAdmin->syncRoles(['Institution Admin']);

        $this->actingAs($institutionAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Roles & Permissions')
            ->assertSeeText('Users')
            ->assertSeeText('Departments')
            ->assertDontSeeText('Record Payment');

        $this->get(route('admin.payments.create'))->assertForbidden();
    }

    #[Test]
    public function accountant_has_finance_permissions_only(): void
    {
        $this->seedRolesAndPermissions();

        $accountant = Role::where('name', 'Accountant')->first();

        $this->assertTrue($accountant->hasPermissionTo('invoices.create'));
        $this->assertTrue($accountant->hasPermissionTo('payments.create'));
        $this->assertTrue($accountant->hasPermissionTo('fee_structures.create'));
        $this->assertTrue($accountant->hasPermissionTo('reports.financial'));

        // Accountant must NOT manage roles/users
        $this->assertFalse($accountant->hasPermissionTo('users.create'));
        $this->assertFalse($accountant->hasPermissionTo('roles_and_permissions.create'));

        // Accountant must NOT have academic write permissions
        $this->assertFalse($accountant->hasPermissionTo('students.create'));
        $this->assertFalse($accountant->hasPermissionTo('exams.create'));
        $this->assertFalse($accountant->hasPermissionTo('marks.update'));
    }

    #[Test]
    public function student_has_own_data_permissions_only(): void
    {
        $this->seedRolesAndPermissions();

        $student = Role::where('name', 'Student')->first();

        $this->assertTrue($student->hasPermissionTo('students.read'));
        $this->assertTrue($student->hasPermissionTo('classes.read'));
        $this->assertTrue($student->hasPermissionTo('report_card.read'));
        $this->assertTrue($student->hasPermissionTo('announcements.read'));

        // Student must NOT have write permissions
        $this->assertFalse($student->hasPermissionTo('students.create'));
        $this->assertFalse($student->hasPermissionTo('attendance.create'));
        $this->assertFalse($student->hasPermissionTo('marks.update'));
    }

    #[Test]
    public function guardian_is_read_only_for_linked_student_workflows(): void
    {
        $this->seedRolesAndPermissions();

        $guardian = Role::where('name', 'Guardian')->firstOrFail();
        foreach (['attendance.read', 'results.read', 'report_card.read', 'invoices.read', 'payments.read'] as $permission) {
            $this->assertTrue($guardian->hasPermissionTo($permission));
        }

        foreach (['students.create', 'attendance.create', 'results.publish', 'invoices.create', 'payments.create'] as $permission) {
            $this->assertFalse($guardian->hasPermissionTo($permission));
        }
    }

    #[Test]
    public function librarian_has_library_permissions_and_cannot_open_admin_routes(): void
    {
        $this->seedRolesAndPermissions();
        $librarianRole = Role::findByName('Librarian');

        foreach (['library.read', 'library.create', 'library.update', 'library.delete', 'students.read'] as $permission) {
            $this->assertTrue($librarianRole->hasPermissionTo($permission));
        }
        $this->assertFalse($librarianRole->hasPermissionTo('users.create'));
        $this->assertFalse($librarianRole->hasPermissionTo('roles_and_permissions.update'));

        $librarian = User::create([
            'name' => 'Demo Librarian',
            'email' => 'demo-librarian@school.test',
            'password' => 'password',
            'role' => 'librarian',
        ]);
        $librarian->syncRoles(['Librarian']);

        $this->actingAs($librarian)->get(route('library.dashboard'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    #[Test]
    public function registrar_demo_user_can_be_created_with_correct_role(): void
    {
        $this->seedRolesAndPermissions();

        $registrar = User::updateOrCreate(
            ['email' => 'registrar@school.test'],
            [
                'name' => 'Demo Registrar',
                'password' => 'password',
                'role' => 'registrar',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $registrar->syncRoles(['Registrar']);

        $this->assertNotNull($registrar);
        $this->assertEquals('registrar', $registrar->role);
        $this->assertTrue($registrar->hasRole('Registrar'));
        $this->assertTrue($registrar->is_active);
    }

    #[Test]
    public function accountant_demo_user_can_be_created_with_correct_role(): void
    {
        $this->seedRolesAndPermissions();

        $accountant = User::updateOrCreate(
            ['email' => 'accountant@school.test'],
            [
                'name' => 'Demo Accountant',
                'password' => 'password',
                'role' => 'accountant',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $accountant->syncRoles(['Accountant']);

        $this->assertNotNull($accountant);
        $this->assertEquals('accountant', $accountant->role);
        $this->assertTrue($accountant->hasRole('Accountant'));
        $this->assertTrue($accountant->is_active);
    }

    #[Test]
    public function parent_user_does_not_receive_student_spatie_role(): void
    {
        $this->seedRolesAndPermissions();

        // Create a user with role column = 'parent' (guardian/parent record)
        $parent = User::create([
            'name' => 'Demo Guardian',
            'email' => 'guardian@school.test',
            'password' => 'password',
            'role' => 'parent',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Parents must NOT be assigned the Student spatie role per SOP §A9
        $this->assertFalse($parent->hasRole('Student'));
        $this->assertEquals('parent', $parent->role);
    }

    #[Test]
    public function unauthorized_user_cannot_access_admin_routes(): void
    {
        $this->seedRolesAndPermissions();

        $student = User::create([
            'name' => 'Student User',
            'email' => 'student@school.test',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);
        $student->syncRoles(['Student']);

        $response = $this->actingAs($student)->get(route('admin.users.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($student)->get(route('admin.departments.index'));
        $response->assertStatus(403);
    }

    #[Test]
    public function teacher_cannot_access_other_teachers_class_marks(): void
    {
        $this->seedRolesAndPermissions();

        $teacher = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $teacher->syncRoles(['Teacher']);

        // Teacher should not access admin user management
        $response = $this->actingAs($teacher)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('teacher.dashboard'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function login_demo_accounts_match_all_builtin_roles(): void
    {
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])->run();

        $demoAccounts = [
            'superadmin@school.test' => 'Super Admin',
            'admin@school.test' => 'Institution Admin',
            'registrar@school.test' => 'Registrar',
            'teacher@school.test' => 'Teacher',
            'accountant@school.test' => 'Accountant',
            'librarian@school.test' => 'Librarian',
            'student@school.test' => 'Student',
            'guardian@school.test' => 'Guardian',
        ];

        foreach ($demoAccounts as $email => $expectedRole) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "Demo account {$email} should be seeded");
            $this->assertTrue($user->hasRole($expectedRole),
                "User {$email} should have role {$expectedRole}");
            $this->assertTrue(Hash::check('password', $user->password),
                "Demo account {$email} should use the documented password");

            $response = $this->post(route('login.attempt'), [
                'email' => $email,
                'password' => 'password',
            ]);

            if ($user->requiresTwoFactor()) {
                $response->assertRedirect(route('two-factor.setup'))
                    ->assertSessionHas('two_factor.pending_user_id', $user->id);
                $this->get(route('two-factor.setup'))
                    ->assertOk()
                    ->assertSee(config('school.local_demo_two_factor_setup_code'));
                $this->post(route('two-factor.cancel'))->assertRedirect(route('login'));
            } else {
                $response->assertRedirect(route($user->dashboardRoute()));
                $this->assertAuthenticatedAs($user);
                $this->assertNotNull($user->fresh()->last_login_at);
                $this->assertFalse(session()->has('two_factor.pending_user_id'));
                $this->get(route('two-factor.setup'))->assertForbidden();
                $this->post(route('logout'))->assertRedirect(route('login'));
            }
        }

        $this->assertFalse(User::where('email', 'superadmin@school.test')->first()->must_change_password);
        $this->get(route('login'))
            ->assertSee('data-email="superadmin@school.test" data-password="password"', false);
        $this->get(route('login'))
            ->assertSee('data-email="librarian@school.test" data-password="password"', false);

        // Parent must NOT be a demo login role
        $this->assertFalse(Role::where('name', 'Parent')->exists());
    }

    #[Test]
    public function invalid_login_error_is_attached_to_the_password_field(): void
    {
        User::create([
            'name' => 'Login Error Test',
            'email' => 'login-error@school.test',
            'password' => 'correct-password',
            'role' => 'librarian',
        ]);

        $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'login-error@school.test',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['password']);

        $this->assertFalse(session('errors')->getBag('default')->has('email'));
    }

    #[Test]
    public function local_demo_seeder_repairs_and_enables_the_librarian_login(): void
    {
        $this->seedRolesAndPermissions();
        $librarian = User::create([
            'name' => 'Old Librarian',
            'email' => 'librarian@school.test',
            'password' => 'old-password',
            'role' => 'librarian',
            'is_active' => false,
            'must_change_password' => true,
        ]);

        $this->artisan('db:seed', ['--class' => \Database\Seeders\LocalDemoCredentialsSeeder::class])->run();

        $librarian->refresh();
        $this->assertTrue($librarian->is_active);
        $this->assertTrue($librarian->hasRole('Librarian'));
        $this->assertTrue(Hash::check('password', $librarian->password));

        $this->post(route('login.attempt'), [
            'email' => 'librarian@school.test',
            'password' => 'password',
        ])->assertRedirect(route('library.dashboard'));

        $this->assertAuthenticatedAs($librarian);
    }

    #[Test]
    public function non_admin_login_does_not_require_two_factor_even_if_it_was_previously_enabled(): void
    {
        $this->seedRolesAndPermissions();
        $google2fa = app(Google2FA::class);
        $teacher = User::create([
            'name' => 'Teacher Two-Factor',
            'email' => 'teacher-two-factor@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'two_factor_secret' => $google2fa->generateSecretKey(),
            'two_factor_enabled_at' => now(),
        ]);
        $teacher->syncRoles(['Teacher']);

        $this->post(route('login.attempt'), [
            'email' => $teacher->email,
            'password' => 'password',
        ])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($teacher);
        $this->assertNotNull($teacher->fresh()->last_login_at);
        $this->assertFalse(session()->has('two_factor.pending_user_id'));
        $this->get(route('two-factor.setup'))->assertForbidden();
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    #[Test]
    public function login_page_hides_local_demo_credentials_outside_local_environments(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get(route('login'))
            ->assertDontSee('Local demo accounts')
            ->assertDontSee('data-email="superadmin@school.test"', false);
    }
}
