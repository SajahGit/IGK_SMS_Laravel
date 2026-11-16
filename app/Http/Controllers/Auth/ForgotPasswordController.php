<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('Auth.AuthForgotPassword');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();
        if ($user && ($user->hasRole('Student') || $user->role === 'student')) {
            return back()->withErrors([
                'email' => 'Student passwords cannot be reset self-service. Please contact the school administrator.',
            ]);
        }

        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'A password reset link has been sent to your email address.');
    }
}
