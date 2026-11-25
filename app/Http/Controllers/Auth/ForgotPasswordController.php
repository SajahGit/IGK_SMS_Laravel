<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('Auth.AuthForgotPassword');
    }

    public function sendResetLinkEmail(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $email = $otpService->normalizeEmail((string) $request->input('email'));
        $user = User::query()->where('email', $email)->first();

        if ($user && ($user->hasRole('Student') || strtolower((string) $user->role) === 'student')) {
            return back()->withErrors([
                'email' => 'Student passwords cannot be reset self-service. Please contact the school administrator.',
            ]);
        }

        $result = $otpService->sendOtp($email, $request->ip());

        if ($result['status'] === 'mail_failed') {
            $errorMsg = ($result['reason'] ?? 'smtp') === 'network'
                ? 'Unable to connect to Gmail SMTP (smtp.gmail.com). Please check your internet connection and try again.'
                : 'Unable to send verification email right now. Please check mail configuration or try again later.';

            return back()->withInput()->withErrors([
                'email' => $errorMsg,
            ]);
        }

        $request->session()->put('password_reset.email', $email);

        if ($result['status'] === 'cooldown') {
            $seconds = $result['cooldown_remaining'] ?? 60;

            return redirect()->route('password.otp.form')
                ->with('status', "A verification code was recently sent. Please check your inbox or wait {$seconds} seconds to resend.");
        }

        return redirect()->route('password.otp.form')
            ->with('status', 'If an eligible administrator account exists for that email, a 6-digit verification code has been sent.');
    }

    public function showOtpForm(Request $request, PasswordResetOtpService $otpService): View|RedirectResponse
    {
        $email = (string) $request->session()->get('password_reset.email', '');

        if ($email === '') {
            return redirect()->route('password.forgot');
        }

        return view('Auth.AuthVerifyResetOtp', [
            'email' => $email,
            'cooldownRemaining' => $otpService->getResendCooldownRemaining($email),
        ]);
    }

    public function verifyOtp(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $email = (string) $request->session()->get('password_reset.email', '');

        if ($email === '') {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Please enter your administrator email address first.']);
        }

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $result = $otpService->verifyOtp($email, (string) $request->input('otp'));

        if (! $result['valid']) {
            $message = match ($result['reason'] ?? 'invalid') {
                'expired' => 'This verification code has expired. Please request a new 6-digit code.',
                'max_attempts' => 'Maximum incorrect attempts reached. Please request a new 6-digit code.',
                default => 'The verification code is invalid or has expired.',
            };

            return back()->withErrors(['otp' => $message]);
        }

        $request->session()->put('password_reset.token', $result['token']);

        return redirect()->route('password.reset', ['token' => $result['token']]);
    }

    public function resendOtp(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $email = (string) $request->session()->get('password_reset.email', '');

        if ($email === '') {
            return redirect()->route('password.forgot');
        }

        $result = $otpService->sendOtp($email, $request->ip());

        if ($result['status'] === 'cooldown') {
            $seconds = $result['cooldown_remaining'] ?? 60;

            return back()->withErrors([
                'otp' => "Please wait {$seconds} seconds before requesting another code.",
            ]);
        }

        if ($result['status'] === 'mail_failed') {
            return back()->withErrors([
                'otp' => 'Unable to send verification email right now. Please try again later.',
            ]);
        }

        return back()->with('status', 'If an eligible administrator account exists for that email, a new 6-digit verification code has been sent.');
    }
}
