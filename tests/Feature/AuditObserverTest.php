<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_model_changes_are_audited_without_storing_passwords(): void
    {
        $actor = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'admin-secret',
            'role' => 'admin',
        ]);
        $this->actingAs($actor);

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $department->update(['name' => 'Applied Science']);
        $department->delete();

        $createdLogs = AuditLog::where('subject_type', Department::class)
            ->where('subject_id', $department->id)
            ->orderBy('id')
            ->get();

        $this->assertSame(['created', 'updated', 'deleted'], $createdLogs->pluck('action')->all());
        $this->assertSame('Administrator', $createdLogs->first()->user->name);
        $this->assertSame('Science', $createdLogs[1]->old_values['name']);
        $this->assertSame('Applied Science', $createdLogs[1]->new_values['name']);

        $newUser = User::create([
            'name' => 'New Teacher',
            'email' => 'teacher@example.test',
            'password' => 'teacher-secret',
            'role' => 'teacher',
        ]);
        $userLog = AuditLog::where('subject_type', User::class)
            ->where('subject_id', $newUser->id)
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $userLog->new_values);
        $this->assertSame($actor->id, $userLog->user_id);
    }
}
