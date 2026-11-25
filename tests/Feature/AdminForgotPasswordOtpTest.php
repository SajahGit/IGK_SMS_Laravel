<?php

namespace Tests\Feature;

use App\Mail\PasswordChangedConfirmationMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminForgotPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $institutionAdmin;
    private User $teacher;
    private User $accountant;
    private User $student;
    private string $admin2faSecret;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Institution Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $google2fa = app(Google2FA::class);
        $this->admin2faSecret = $google2fa->generateSecretKey();

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.otp@school.test',
            'password' => 'OldAdminPass123!',
            'role' => 'super-admin',
            'is_active' => true,
            'two_factor_secret' => $this->admin2faSecret,
            'two_factor_enabled_at' => now(),
        ]);
        $this->superAdmin->syncRoles(['Super Admin']);

        $this->institutionAdmin = User::create([
            'name' => 'Institution Admin',
            'email' => 'admin.otp@school.test',
            'password' => 'OldAdminPass123!',
            'role' => 'admin',
            'is_active' => true,
            'two_factor_secret' => $google2fa->generateSecretKey(),
            'two_factor_enabled_at' => now(),
        ]);
        $this->institutionAdmin->syncRoles(['Institution Admin']);

        $this->teacher = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher.otp@school.test',
            'password' => 'TeacherPass123!',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->teacher->syncRoles(['Teacher']);

        $this->accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant.otp@school.test',
            'password' => 'AccountantPass123!',
            'role' => 'accountant',
            'is_active' => true,
        ]);
        $this->accountant->syncRoles(['Accountant']);

        $this->student = User::create([
            'name' => 'Student User',
            'email' => 'student.otp@school.test',
            'password' => 'StudentPass123!',
            'role' => 'student',
            'is_active' => true,
        ]);
        $this->student->syncRoles(['Student']);
    }

    #[Test]
    public function login_page_displays_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.forgot'), false)
            ->assertSee('Forgot password?');
    }

    #[Test]
    public function super_admin_and_institution_admin_can_complete_otp_password_reset_and_2fa_remains_mandatory(): void
    {
        Mail::fake();

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'existing-session-id-123',
                'user_id' => $this->superAdmin->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'dGVzdA==',
                'last_activity' => time(),
            ]);
        }

        // Step 1: Request OTP
        $response = $this->post(route('password.email'), [
            'email' => $this->superAdmin->email,
        ]);
        $response->assertRedirect(route('password.otp.form'));

        $capturedOtp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;

            return $mail->hasTo($this->superAdmin->email);
        });

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $capturedOtp);

        // OTP is hashed in DB, never stored in plaintext
        $otpRecord = PasswordResetOtp::where('user_id', $this->superAdmin->id)->latest('id')->first();
        $this->assertNotNull($otpRecord);
        $this->assertNotSame($capturedOtp, $otpRecord->otp_hash);
        $this->assertTrue(Hash::check($capturedOtp, $otpRecord->otp_hash));

        // Step 2: Verify OTP
        $verifyResponse = $this->post(route('password.otp.verify'), [
            'otp' => $capturedOtp,
        ]);
        $verifyResponse->assertRedirect();
        $resetToken = session('password_reset.token');
        $this->assertNotEmpty($resetToken);

        // OTP cannot be reused
        $this->post(route('password.otp.verify'), ['otp' => $capturedOtp])
            ->assertSessionHasErrors(['otp']);

        // Step 3: Reset Password
        $resetResponse = $this->post(route('password.update'), [
            'email' => $this->superAdmin->email,
            'token' => $resetToken,
            'password' => 'NewStrongAdmin99!',
            'password_confirmation' => 'NewStrongAdmin99!',
        ]);

        $resetResponse->assertRedirect(route('login'))
            ->assertSessionHas('success');

        // User is NOT auto-logged in
        $this->assertGuest();

        // Password updated & 2FA preserved
        $freshAdmin = $this->superAdmin->fresh();
        $this->assertTrue(Hash::check('NewStrongAdmin99!', $freshAdmin->password));
        $this->assertSame($this->admin2faSecret, $freshAdmin->two_factor_secret);
        $this->assertNotNull($freshAdmin->two_factor_enabled_at);

        // Existing session revoked
        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', ['id' => 'existing-session-id-123']);
        }

        // Confirmation email sent
        Mail::assertSent(PasswordChangedConfirmationMail::class, function (PasswordChangedConfirmationMail $mail) {
            return $mail->hasTo($this->superAdmin->email);
        });

        // Reset token cannot be reused
        $this->post(route('password.update'), [
            'email' => $this->superAdmin->email,
            'token' => $resetToken,
            'password' => 'AnotherPass99!',
            'password_confirmation' => 'AnotherPass99!',
        ])->assertRedirect(route('password.forgot'));

        // Logging in with new password MUST still require Google Authenticator 2FA
        $loginResponse = $this->post(route('login.attempt'), [
            'email' => $this->superAdmin->email,
            'password' => 'NewStrongAdmin99!',
        ]);
        $loginResponse->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        // Complete 2FA login
        $totp = app(Google2FA::class)->getCurrentOtp($this->admin2faSecret);
        $this->post(route('two-factor.challenge'), ['code' => $totp])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    #[Test]
    public function institution_admin_can_also_request_and_verify_otp(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => $this->institutionAdmin->email])
            ->assertRedirect(route('password.otp.form'));

        Mail::assertSent(PasswordResetOtpMail::class, fn ($m) => $m->hasTo($this->institutionAdmin->email));
    }

    #[Test]
    public function non_admin_roles_and_unknown_emails_never_receive_otp(): void
    {
        Mail::fake();

        // Teacher & Accountant (employees) cannot receive OTPs; response hides account status
        foreach ([$this->teacher->email, $this->accountant->email, 'nonexistent@school.test'] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.otp.form'));
        }

        // Student explicitly blocked per StudentPasswordRestrictionTest
        $this->post(route('password.email'), ['email' => $this->student->email])
            ->assertSessionHasErrors(['email']);

        Mail::assertNothingSent();
        $this->assertSame(0, PasswordResetOtp::count());
    }

    #[Test]
    public function expired_otp_and_max_attempts_are_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => $this->superAdmin->email]);

        $capturedOtp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;

            return true;
        });

        // 5 wrong attempts burn the OTP
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.otp.verify'), ['otp' => '000000'])
                ->assertSessionHasErrors(['otp']);
        }

        // Now even the real OTP is rejected
        $this->post(route('password.otp.verify'), ['otp' => $capturedOtp])
            ->assertSessionHasErrors(['otp']);

        // Travel past 5 minutes on a fresh OTP -> expired
        $this->travel(65)->seconds();
        $this->post(route('password.otp.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->travel(6)->minutes();

        Mail::assertSent(PasswordResetOtpMail::class, 2);
        $this->post(route('password.otp.verify'), ['otp' => $capturedOtp])
            ->assertSessionHasErrors(['otp']);
    }

    #[Test]
    public function resend_enforces_cooldown_and_weak_or_mismatched_passwords_are_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => $this->superAdmin->email]);

        // Immediate resend blocked by 60s cooldown
        $this->post(route('password.otp.resend'))
            ->assertSessionHasErrors(['otp']);

        $capturedOtp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;

            return true;
        });

        $this->post(route('password.otp.verify'), ['otp' => $capturedOtp]);
        $token = session('password_reset.token');

        // Weak password
        $this->post(route('password.update'), [
            'email' => $this->superAdmin->email,
            'token' => $token,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['password']);

        // Mismatched password
        $this->post(route('password.update'), [
            'email' => $this->superAdmin->email,
            'token' => $token,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'DifferentPass123!',
        ])->assertSessionHasErrors(['password']);

        // Original password still works
        $this->assertTrue(Hash::check('OldAdminPass123!', $this->superAdmin->fresh()->password));
    }

    #[Test]
    public function mail_delivery_failure_invalidates_otp_and_reports_error(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP connection refused'));

        $response = $this->from(route('password.forgot'))
            ->post(route('password.email'), ['email' => $this->superAdmin->email]);

        $response->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors(['email']);

        $record = PasswordResetOtp::where('user_id', $this->superAdmin->id)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->used_at);
    }
}
