<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private const PENDING_USER = 'two_factor.pending_user_id';

    private const RECOVERY_CODES_ONCE = 'two_factor.recovery_codes_once';

    public function showChallenge(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->two_factor_enabled_at) {
            return redirect()->route('two-factor.setup');
        }

        return view('Auth.AuthTwoFactorChallenge');
    }

    public function verifyChallenge(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->two_factor_enabled_at || ! $user->two_factor_secret) {
            return redirect()->route('two-factor.setup');
        }

        $result = DB::transaction(function () use ($google2fa, $request, $user) {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser || ! $lockedUser->is_active || ! $lockedUser->two_factor_enabled_at) {
                return ['valid' => false, 'recovery' => false];
            }

            $code = trim((string) $request->input('code'));
            if (preg_match('/^\d{6}$/', $code) === 1) {
                $counter = $google2fa->verifyKeyNewer(
                    $lockedUser->two_factor_secret,
                    $code,
                    $lockedUser->two_factor_last_used_at ?? 0,
                    1
                );

                if ($counter !== false) {
                    $lockedUser->forceFill(['two_factor_last_used_at' => $counter])->save();

                    return ['valid' => true, 'recovery' => false];
                }
            }

            $normalizedCode = strtoupper(str_replace(['-', ' '], '', $code));
            $recoveryCodes = $lockedUser->two_factor_recovery_codes ?? [];

            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check($normalizedCode, $hashedCode)) {
                    unset($recoveryCodes[$index]);
                    $lockedUser->forceFill([
                        'two_factor_recovery_codes' => array_values($recoveryCodes),
                    ])->save();

                    return ['valid' => true, 'recovery' => true];
                }
            }

            return ['valid' => false, 'recovery' => false];
        });

        if (! $result['valid']) {
            return back()->withErrors(['code' => 'The authentication code is invalid or has already been used.']);
        }

        if ($result['recovery']) {
            $request->session()->put('two_factor.recovery_code_used', true);
        }

        return $this->finishLogin($request, $user);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget([
            self::PENDING_USER,
            self::RECOVERY_CODES_ONCE,
            'two_factor.remember',
            'two_factor.recovery_code_used',
            'two_factor.newly_enabled',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showSetup(Request $request, Google2FA $google2fa): View|RedirectResponse
    {
        $user = $this->setupUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if ($encryptedCodes = $request->session()->get(self::RECOVERY_CODES_ONCE)) {
            return view('Auth.AuthTwoFactorSetup', [
                'secret' => null,
                'recoveryCodes' => json_decode(Crypt::decryptString($encryptedCodes), true, 512, JSON_THROW_ON_ERROR),
                'enabled' => true,
            ]);
        }

        if ($user->two_factor_enabled_at) {
            return view('Auth.AuthTwoFactorSetup', [
                'secret' => null,
                'recoveryCodes' => null,
                'enabled' => true,
            ]);
        }

        if (! $user->two_factor_secret) {
            $user->forceFill(['two_factor_secret' => $google2fa->generateSecretKey()])->save();
        }

        return view('Auth.AuthTwoFactorSetup', [
            'secret' => $user->two_factor_secret,
            'recoveryCodes' => null,
            'enabled' => false,
            'demoSetupCode' => $this->localDemoSetupCode($user),
        ]);
    }

    public function confirmSetup(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
            'current_password' => ['nullable', 'string'],
        ]);

        $user = $this->setupUser($request);

        if (! $user || $user->two_factor_enabled_at || ! $user->two_factor_secret) {
            return redirect()->route('login');
        }

        if (Auth::check() && ! Hash::check((string) $request->input('current_password'), Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $code = $request->string('code')->toString();
        $counter = $google2fa->verifyKeyNewer($user->two_factor_secret, $code, 0, 1);
        $demoSetupCode = $this->localDemoSetupCode($user);
        $usedDemoSetupCode = $counter === false && $demoSetupCode !== null
            && hash_equals($demoSetupCode, $code);
        if ($counter === false && ! $usedDemoSetupCode) {
            return back()->withErrors(['code' => 'The authentication code is invalid.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_enabled_at' => now(),
            'two_factor_last_used_at' => $counter === false ? 0 : $counter,
            'two_factor_recovery_codes' => array_map(
                fn (string $code) => Hash::make($code),
                $recoveryCodes
            ),
        ])->save();

        $request->session()->put(
            self::RECOVERY_CODES_ONCE,
            Crypt::encryptString(json_encode($recoveryCodes, JSON_THROW_ON_ERROR))
        );
        $request->session()->put('two_factor.newly_enabled', true);

        if (Auth::check()) {
            AuditLogger::log('two_factor_enabled', $user);
        }

        return redirect()->route('two-factor.setup');
    }

    public function completeSetup(Request $request): RedirectResponse
    {
        if (! $request->session()->has(self::RECOVERY_CODES_ONCE)) {
            return redirect()->route('login');
        }

        $request->session()->forget(self::RECOVERY_CODES_ONCE);

        if (Auth::check()) {
            $request->session()->forget('two_factor.newly_enabled');

            $authenticatedUser = Auth::user();
            if (! ($authenticatedUser instanceof User)) {
                return redirect()->route('login');
            }

            return redirect()->route($authenticatedUser->dashboardRoute());
        }

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return $this->finishLogin($request, $user);
    }

    public function regenerateRecoveryCodes(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = Auth::user();
        if (! ($user instanceof User) || ! $this->requiresTwoFactor($user)) {
            abort(403);
        }

        if (! $user->two_factor_enabled_at || ! $user->two_factor_secret) {
            return redirect()->route('two-factor.setup');
        }

        $result = DB::transaction(function () use ($google2fa, $request, $user) {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser || ! $lockedUser->is_active || ! $lockedUser->two_factor_enabled_at
                || ! $this->requiresTwoFactor($lockedUser)) {
                return ['error' => 'code'];
            }

            if (! Hash::check((string) $request->input('current_password'), $lockedUser->password)) {
                return ['error' => 'current_password'];
            }

            $counter = $google2fa->verifyKeyNewer(
                $lockedUser->two_factor_secret,
                (string) $request->input('code'),
                $lockedUser->two_factor_last_used_at ?? 0,
                1
            );

            if ($counter === false) {
                return ['error' => 'code'];
            }

            $recoveryCodes = $this->generateRecoveryCodes();
            $lockedUser->forceFill([
                'two_factor_last_used_at' => $counter,
                'two_factor_recovery_codes' => array_map(
                    fn (string $code) => Hash::make($code),
                    $recoveryCodes
                ),
            ])->save();

            return ['recoveryCodes' => $recoveryCodes];
        });

        if (isset($result['error'])) {
            $message = $result['error'] === 'current_password'
                ? 'The current password is incorrect.'
                : 'The authentication code is invalid or has already been used.';

            return back()->withErrors([$result['error'] => $message]);
        }

        $request->session()->put(
            self::RECOVERY_CODES_ONCE,
            Crypt::encryptString(json_encode($result['recoveryCodes'], JSON_THROW_ON_ERROR))
        );
        AuditLogger::log('two_factor_recovery_codes_regenerated', $user);

        return redirect()->route('two-factor.setup');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::PENDING_USER);
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return $user && $user->is_active && $this->requiresTwoFactor($user) ? $user : null;
    }

    private function setupUser(Request $request): ?User
    {
        if (Auth::check()) {
            abort_unless($this->requiresTwoFactor(Auth::user()), 403);

            return Auth::user();
        }

        return $this->pendingUser($request);
    }

    private function localDemoSetupCode(User $user): ?string
    {
        if (! app()->environment(['local', 'testing'])
            || ! in_array($user->email, config('school.demo_emails', []), true)) {
            return null;
        }

        $code = (string) config('school.local_demo_two_factor_setup_code');

        return preg_match('/^\d{6}$/', $code) === 1 ? $code : null;
    }

    private function requiresTwoFactor(User $user): bool
    {
        return $user->is_active && $user->requiresTwoFactor();
    }

    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 12))
            ->map(fn () => strtoupper(bin2hex(random_bytes(8))))
            ->all();
    }

    private function finishLogin(Request $request, User $user): RedirectResponse
    {
        $remember = (bool) $request->session()->get('two_factor.remember', false);
        $recoveryCodeUsed = (bool) $request->session()->get('two_factor.recovery_code_used', false);
        $twoFactorEnabled = (bool) $request->session()->get('two_factor.newly_enabled', false);
        $request->session()->forget([
            self::PENDING_USER,
            'two_factor.remember',
            'two_factor.recovery_code_used',
            'two_factor.newly_enabled',
        ]);

        Auth::login($user, $remember);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        AuditLogger::log('logged_in', $user, null, ['email' => $user->email, 'two_factor' => true]);
        if ($recoveryCodeUsed) {
            AuditLogger::log('two_factor_recovery_code_used', $user);
        }
        if ($twoFactorEnabled) {
            AuditLogger::log('two_factor_enabled', $user);
        }

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route($user->dashboardRoute()));
    }
}
