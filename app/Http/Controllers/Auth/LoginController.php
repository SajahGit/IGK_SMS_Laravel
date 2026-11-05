<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('Auth.AuthLogin');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::validate($credentials)) {
            AuditLogger::log('login_failed', User::class, null, ['email' => $credentials['email']]);

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user->is_active) {
            AuditLogger::log('login_denied_disabled', $user, null, ['email' => $user->email]);

            return back()->withErrors(['email' => 'Account disabled, contact administrator.'])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();
        AuditLogger::log('logged_in', $user, null, ['email' => $user->email]);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            AuditLogger::log('logged_out', $request->user(), null, ['email' => $request->user()->email]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
