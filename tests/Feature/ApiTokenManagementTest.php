<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_created_token_appears_in_active_tokens(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'password' => 'password',
        ]);

        $this->actingAs($student)
            ->post(route('account.api-tokens.store'), [
                'name' => 'Student Portal QA Token',
                'abilities' => ['profile:read', 'academic:read'],
                'current_password' => 'password',
            ])
            ->assertRedirect(route('account.api-tokens.index'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $student->id,
            'name' => 'Student Portal QA Token',
        ]);

        $this->get(route('account.api-tokens.index'))
            ->assertOk()
            ->assertSee('Student Portal QA Token')
            ->assertSee('placeholder="e.g. Mobile app integration"', false)
            ->assertSee('text-break', false);
    }

    public function test_expired_tokens_are_not_listed_as_active(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'password' => 'password',
        ]);

        $student->createToken(
            'Expired Demo Token',
            ['profile:read'],
            now()->subDay()
        );

        $this->actingAs($student)
            ->get(route('account.api-tokens.index'))
            ->assertOk()
            ->assertSee('Expired tokens')
            ->assertSee('Expired Demo Token')
            ->assertSee('No active tokens.');
    }
}
