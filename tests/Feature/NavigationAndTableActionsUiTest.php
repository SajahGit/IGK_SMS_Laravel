<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAndTableActionsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_teacher_table_renders_actions_column_with_proper_min_width_and_actions_container(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Campus', 'code' => 'MAIN'], ['is_active' => true, 'is_main' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => $branch->id]);
        $admin->syncRoles(['Institution Admin']);

        $teacherUser = User::factory()->create(['role' => 'teacher', 'branch_id' => $branch->id]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.teachers.index'));
        $response->assertOk();
        $response->assertSee('min-width: 220px;', false);
        $response->assertSee('teacher-actions', false);
        $response->assertDontSee('btn-group btn-group-sm', false);
        $response->assertSee('Transfer', false);
        $response->assertSee('Edit', false);
    }

    public function test_teacher_profile_tabs_navigation_renders_full_width_container(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Campus', 'code' => 'MAIN'], ['is_active' => true, 'is_main' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => $branch->id]);
        $admin->syncRoles(['Institution Admin']);

        $teacherUser = User::factory()->create(['role' => 'teacher', 'branch_id' => $branch->id]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.teachers.show', $teacher));
        $response->assertOk();
        $response->assertSee('id="teacherProfileTabs"', false);
        $response->assertSee('w-100', false);
        $response->assertSee('Salary & Super Admin Deductions', false);
        $response->assertSee('Teaching Allocations & Campus', false);
        $response->assertSee('Leaves & Attendance', false);
    }

    public function test_curricula_and_users_render_table_actions_and_full_width_tabs(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Campus', 'code' => 'MAIN'], ['is_active' => true, 'is_main' => true]);
        $superadmin = User::factory()->create(['role' => 'super-admin', 'branch_id' => $branch->id]);
        $superadmin->syncRoles(['Super Admin']);

        $curriculaResponse = $this->actingAs($superadmin)->get(route('admin.curricula.index'));
        $curriculaResponse->assertOk();
        $curriculaResponse->assertSee('id="curriculumTabs"', false);
        $curriculaResponse->assertSee('w-100', false);

        $usersResponse = $this->actingAs($superadmin)->get(route('admin.users.index'));
        $usersResponse->assertOk();
        $usersResponse->assertSee('table-actions', false);
        $usersResponse->assertSee('min-width: 170px;', false);
    }

    public function test_teacher_classes_and_exams_render_table_actions_with_min_width(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Campus', 'code' => 'MAIN'], ['is_active' => true, 'is_main' => true]);
        $teacherUser = User::factory()->create(['role' => 'teacher', 'branch_id' => $branch->id]);
        $teacherUser->syncRoles(['Teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $schoolClass = SchoolClass::factory()->create(['branch_id' => $branch->id]);
        $subject = Subject::factory()->create();
        ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $classesResponse = $this->actingAs($teacherUser)->get(route('teacher.classes.index'));
        $classesResponse->assertOk();
        $classesResponse->assertSee('table-actions', false);
        $classesResponse->assertSee('min-width: 220px;', false);

        $examsResponse = $this->actingAs($teacherUser)->get(route('teacher.exams.index'));
        $examsResponse->assertOk();
        $examsResponse->assertSee('min-width: 220px;', false);
    }

    public function test_glass_css_has_full_width_tab_system_and_overflow_containment(): void
    {
        $css = file_get_contents(public_path('css/glass.css'));

        $this->assertStringContainsString('overflow-x: clip;', $css);
        $this->assertStringContainsString('Modern Tab Navigation System', $css);
        $this->assertStringContainsString('.teacher-table { min-width: 72rem; }', $css);
        $this->assertStringContainsString('.teacher-actions, .table-actions', $css);
    }
}
