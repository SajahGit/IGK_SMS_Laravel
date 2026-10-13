<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Run after all modules are merged:  php artisan test */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin.dashboard', 'teacher.dashboard', 'student.dashboard', 'parent.dashboard', 'reports.index'] as $needed) {
            if (! \Illuminate\Support\Facades\Route::has($needed)) {
                $this->markTestSkipped('Run this test after all four modules are pasted in.');
            }
        }
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.'@test.local',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    public function test_each_role_is_sent_to_its_own_dashboard(): void
    {
        $map = [
            'admin' => 'admin.dashboard',
            'teacher' => 'teacher.dashboard',
            'student' => 'student.dashboard',
            'parent' => 'parent.dashboard',
        ];

        foreach ($map as $role => $route) {
            $this->actingAs($this->makeUser($role))
                ->get('/dashboard')
                ->assertRedirect(route($route));
        }
    }

    public function test_teacher_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->makeUser('teacher'))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_student_cannot_open_teacher_pages(): void
    {
        $this->actingAs($this->makeUser('student'))
            ->get(route('teacher.dashboard'))
            ->assertForbidden();
    }

    public function test_parent_cannot_open_a_child_that_is_not_linked(): void
    {
        $parent = $this->makeUser('parent');
        Guardian::create(['user_id' => $parent->id, 'name' => 'Parent', 'relationship' => 'Father']);

        $studentUser = User::create([
            'name' => 'Kid', 'email' => 'kid@test.local', 'password' => 'password', 'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'admission_no' => 'ADM-TEST-0001',
            'first_name' => 'Kid', 'last_name' => 'Test', 'status' => 'active',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.attendance.show', $student))
            ->assertForbidden();
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = $this->makeUser('admin');
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
