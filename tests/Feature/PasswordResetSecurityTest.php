<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_generic_hashed_expiring_and_single_use(): void
    {
        Notification::fake();
        $user = User::create([
            'name' => 'Password Reset User',
            'email' => 'reset@example.test',
            'password' => 'CurrentPassword123!',
            'role' => 'admin',
        ]);

        $response = $this->post(route('password.email'), ['email' => $user->email]);
        $response->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $notification = Notification::sent($user, ResetPassword::class)->first();
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
            'token' => $notification->token,
        ]);

        $payload = [
            'email' => $user->email,
            'token' => $notification->token,
            'password' => 'ReplacementPassword123!',
            'password_confirmation' => 'ReplacementPassword123!',
        ];

        $this->post(route('password.update'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertTrue(password_verify($payload['password'], $user->fresh()->password));

        $this->from(route('password.reset', ['email' => $user->email]))
            ->post(route('password.update'), $payload)
            ->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_reset_link_expires_after_the_configured_hour(): void
    {
        Notification::fake();
        $user = User::create([
            'name' => 'Expired Reset User',
            'email' => 'expired-reset@example.test',
            'password' => 'CurrentPassword123!',
            'role' => 'admin',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        $notification = Notification::sent($user, ResetPassword::class)->first();

        $this->travel(61)->minutes();
        $this->from(route('password.reset', ['email' => $user->email]))
            ->post(route('password.update'), [
                'email' => $user->email,
                'token' => $notification->token,
                'password' => 'ReplacementPassword123!',
                'password_confirmation' => 'ReplacementPassword123!',
            ])
            ->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHasErrors('email');

        $this->assertTrue(password_verify('CurrentPassword123!', $user->fresh()->password));
    }

    public function test_password_reset_request_does_not_disclose_unknown_emails(): void
    {
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status', 'A password reset link has been sent to your email address.');

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_login_throttle_is_scoped_to_email_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.attempt'), [
                'email' => 'first@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login.attempt'), [
            'email' => 'second@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect();

        $this->post(route('login.attempt'), [
            'email' => 'first@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_password_reset_requires_a_strong_password(): void
    {
        $this->from(route('password.reset', ['email' => 'any@example.test']))
            ->post(route('password.update'), [
                'email' => 'any@example.test',
                'token' => 'invalid',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_compromised_password_is_rejected_by_the_shared_password_policy(): void
    {
        $password = 'Compromised123!';
        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data)
            {
                return false;
            }
        });
        $user = User::create([
            'name' => 'Compromised Password User',
            'email' => 'compromised@example.test',
            'password' => 'CurrentPassword123!',
            'role' => 'admin',
        ]);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');
    }

    public function test_password_reset_enforces_the_shared_eight_to_thirty_two_character_range(): void
    {
        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data)
            {
                return true;
            }
        });
        $user = User::create([
            'name' => 'Password Length User',
            'email' => 'password-length@example.test',
            'password' => 'CurrentPassword123!',
            'role' => 'admin',
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Aa1234!x',
            'password_confirmation' => 'Aa1234!x',
        ])->assertRedirect(route('login'));

        $tooLong = 'Aa1234!'.str_repeat('x', 26);
        $this->from(route('password.reset', ['email' => $user->email]))
            ->post(route('password.update'), [
                'email' => $user->email,
                'token' => 'invalid',
                'password' => $tooLong,
                'password_confirmation' => $tooLong,
            ])
            ->assertSessionHasErrors('password');
    }
}
