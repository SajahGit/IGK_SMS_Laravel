<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, PasswordResetOtpService $otpService): View|RedirectResponse
    {
        $email = (string) $request->session()->get('password_reset.email', $request->query('email', ''));
        $token = (string) ($request->route('token') ?: $request->query('token', $request->session()->get('password_reset.token', '')));

        if (! $otpService->hasValidResetToken($email, $token)) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'Your password reset session is invalid or has expired. Please request a new code.',
            ]);
        }

        return view('Auth.AuthResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function reset(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $result = $otpService->resetPassword(
            (string) $request->input('email'),
            (string) $request->input('token'),
            (string) $request->input('password'),
            $request->ip()
        );

        if (! $result['reset']) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'This password reset authorization is invalid, expired, or has already been used.',
            ]);
        }

        $request->session()->forget(['password_reset.email', 'password_reset.token']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Your password has been reset successfully. Please log in with your new password and Google Authenticator.');
    }
}
