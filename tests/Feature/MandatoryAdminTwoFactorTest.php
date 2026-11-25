<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MandatoryAdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;
    private User $superAdmin;
    private User $institutionAdmin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->google2fa = app(Google2FA::class);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Institution Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin.strict@school.test',
            'password' => bcrypt('AdminPassword123!'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);
        $this->superAdmin->syncRoles(['Super Admin']);

        $this->institutionAdmin = User::create([
            'name' => 'Campus Admin User',
            'email' => 'admin.strict@school.test',
            'password' => bcrypt('AdminPassword123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->institutionAdmin->syncRoles(['Institution Admin']);

        $this->teacher = User::create([
            'name' => 'Regular Teacher',
            'email' => 'teacher.regular@school.test',
            'password' => bcrypt('TeacherPassword123!'),
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->teacher->syncRoles(['Teacher']);
    }

    #[Test]
    public function super_admin_and_institution_admin_strictly_require_two_factor(): void
    {
        $this->assertTrue($this->superAdmin->requiresTwoFactor());
        $this->assertTrue($this->institutionAdmin->requiresTwoFactor());
        $this->assertFalse($this->teacher->requiresTwoFactor());
    }

    #[Test]
    public function admin_first_login_redirects_to_setup_with_qr_code_svg_and_secret(): void
    {
        $response = $this->post(route('login.attempt'), [
            'email' => $this->superAdmin->email,
            'password' => 'AdminPassword123!',
        ]);

        $response->assertRedirect(route('two-factor.setup'))
            ->assertSessionHas('two_factor.pending_user_id', $this->superAdmin->id);

        $this->assertGuest();

        $setupResponse = $this->get(route('two-factor.setup'));
        $setupResponse->assertOk()
            ->assertSee('<svg', false) // QR Code SVG rendered
            ->assertSee('Scan this QR code in <strong>Google Authenticator</strong>', false)
            ->assertSee('Manual Secret Setup Key')
            ->assertSee('6-Digit Google Authenticator OTP');
    }

    #[Test]
    public function admin_can_verify_and_enable_google_authenticator_2fa_with_valid_totp(): void
    {
        // First attempt login to initiate pending setup
        $this->post(route('login.attempt'), [
            'email' => $this->superAdmin->email,
            'password' => 'AdminPassword123!',
        ]);

        $this->get(route('two-factor.setup'));
        $secret = $this->superAdmin->fresh()->two_factor_secret;
        $this->assertNotNull($secret);

        // Invalid 6-digit code fails
        $invalidResponse = $this->post(route('two-factor.setup'), ['code' => '000000']);
        $invalidResponse->assertSessionHasErrors(['code']);
        $this->assertNull($this->superAdmin->fresh()->two_factor_enabled_at);

        // Valid 6-digit code generated from Google Authenticator secret succeeds
        $validCode = $this->google2fa->getCurrentOtp($secret);
        $confirmResponse = $this->post(route('two-factor.setup'), ['code' => $validCode]);
        $confirmResponse->assertRedirect(route('two-factor.setup'));

        $this->assertNotNull($this->superAdmin->fresh()->two_factor_enabled_at);

        // Setup shows recovery codes
        $codesResponse = $this->get(route('two-factor.setup'));
        $codesResponse->assertOk()
            ->assertSee('Save these recovery codes somewhere safe')
            ->assertSee('I Saved My Recovery Codes');

        // Complete setup and enter dashboard
        $completeResponse = $this->post(route('two-factor.complete'));
        $completeResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    #[Test]
    public function every_subsequent_login_strictly_requires_valid_otp_and_does_not_trust_device(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->institutionAdmin->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        // Attempt login with remember=true
        $response = $this->post(route('login.attempt'), [
            'email' => $this->institutionAdmin->email,
            'password' => 'AdminPassword123!',
            'remember' => true,
        ]);

        $response->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('two_factor.pending_user_id', $this->institutionAdmin->id);
        $this->assertGuest();

        // Challenge page renders without "trust this device" or "remember me"
        $challengePage = $this->get(route('two-factor.challenge'));
        $challengePage->assertOk()
            ->assertSee('Two-Factor Verification')
            ->assertSee('Mandatory on every admin login')
            ->assertDontSee('Remember this device')
            ->assertDontSee('Trust this device');

        // Submit wrong OTP -> rejected
        $wrongOtp = $this->post(route('two-factor.challenge'), ['code' => '999999']);
        $wrongOtp->assertSessionHasErrors(['code']);
        $this->assertGuest();

        // Submit valid TOTP -> logged in
        $validOtp = $this->google2fa->getCurrentOtp($secret);
        $success = $this->post(route('two-factor.challenge'), ['code' => $validOtp]);
        $success->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->institutionAdmin);

        // After logging out, logging in again MUST prompt for 2FA OTP without skipping
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $secondLogin = $this->post(route('login.attempt'), [
            'email' => $this->institutionAdmin->email,
            'password' => 'AdminPassword123!',
        ]);

        $secondLogin->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    #[Test]
    public function non_admin_users_log_in_directly_without_2fa(): void
    {
        $response = $this->post(route('login.attempt'), [
            'email' => $this->teacher->email,
            'password' => 'TeacherPassword123!',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($this->teacher);
    }
}
