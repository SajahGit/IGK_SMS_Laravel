<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAllCampusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_student_list_defaults_to_all_campuses(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->assertExitCode(0);

        $superAdmin = User::create([
            'name' => 'Aisha Rahman',
            'email' => 'aisha.rahman@harbourview.test',
            'password' => 'Password123!',
            'role' => 'super-admin',
        ]);
        $superAdmin->syncRoles(['Super Admin']);

        foreach ([
            ['code' => 'MAIN', 'name' => 'Harbourview Main Campus', 'is_main' => true],
            ['code' => 'WEST', 'name' => 'Harbourview West Campus', 'is_main' => false],
        ] as $index => $branchData) {
            $branch = Branch::create($branchData + ['is_active' => true]);
            $class = SchoolClass::create([
                'name' => 'Grade 7',
                'section' => $index === 0 ? 'A' : 'B',
                'branch_id' => $branch->id,
            ]);
            $studentUser = User::create([
                'name' => $index === 0 ? 'Amara Perera' : 'Mateo Silva',
                'email' => "student.{$index}@harbourview.test",
                'password' => 'Password123!',
                'role' => 'student',
            ]);
            Student::create([
                'user_id' => $studentUser->id,
                'class_id' => $class->id,
                'branch_id' => $branch->id,
                'admission_no' => sprintf('2026-G7-%04d', $index + 1),
            ]);
        }

        $this->actingAs($superAdmin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSeeText('Total matching students: 2')
            ->assertSeeText('Amara Perera')
            ->assertSeeText('Mateo Silva')
            ->assertSeeText('All campuses');

        $this->assertSame('all', session('active_branch_id'));
    }

    public function test_super_admin_can_switch_to_a_single_campus(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->assertExitCode(0);
        $branch = Branch::create([
            'code' => 'MAIN',
            'name' => 'Harbourview Main Campus',
            'is_main' => true,
            'is_active' => true,
        ]);
        $superAdmin = User::create([
            'name' => 'Aisha Rahman',
            'email' => 'campus.admin@harbourview.test',
            'password' => 'Password123!',
            'role' => 'super-admin',
        ]);
        $superAdmin->syncRoles(['Super Admin']);

        $this->actingAs($superAdmin)
            ->post(route('admin.branch-context'), ['branch_id' => $branch->id])
            ->assertRedirect();

        $this->assertSame($branch->id, session('active_branch_id'));
    }
}
