<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CurriculumModuleTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $user = User::updateOrCreate(
            ['email' => 'superadmin@school.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $user->syncRoles(['Super Admin']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    public function test_curriculum_crud_workflow(): void
    {
        $admin = $this->seedAndAdmin();
        $this->actingAs($admin);

        $dept = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'is_active' => true]);
        $program = Program::create(['department_id' => $dept->id, 'name' => 'Computer Science', 'code' => 'CS', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);

        // Index
        $this->get(route('admin.curricula.index'))->assertStatus(200);

        // Create
        $this->post(route('admin.curricula.store'), [
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'term_number' => 1,
            'is_elective' => false,
        ])->assertRedirect(route('admin.curricula.index'));

        $this->assertDatabaseHas('curricula', ['program_id' => $program->id, 'subject_id' => $subject->id, 'term_number' => 1]);
        $curriculum = Curriculum::first();

        // Duplicate blocked
        $this->post(route('admin.curricula.store'), [
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'term_number' => 1,
        ])->assertSessionHasErrors('subject_id');

        // Edit
        $this->get(route('admin.curricula.edit', $curriculum))->assertStatus(200);

        // Update
        $this->put(route('admin.curricula.update', $curriculum), [
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'term_number' => 2,
            'is_elective' => true,
        ])->assertRedirect(route('admin.curricula.index'));

        $this->assertDatabaseHas('curricula', ['id' => $curriculum->id, 'term_number' => 2, 'is_elective' => true]);

        // Destroy
        $this->delete(route('admin.curricula.destroy', $curriculum))->assertRedirect(route('admin.curricula.index'));
        $this->assertDatabaseMissing('curricula', ['id' => $curriculum->id]);
    }

    public function test_teacher_cannot_access_curriculum(): void
    {
        $this->seedAndAdmin();
        $tUser = User::create(['name' => 'Teacher', 'email' => 't@s.t', 'password' => 'password', 'role' => 'teacher']);
        $this->actingAs($tUser);

        $this->get(route('admin.curricula.index'))->assertStatus(403);
        $this->post(route('admin.curricula.store'))->assertStatus(403);
    }
}
