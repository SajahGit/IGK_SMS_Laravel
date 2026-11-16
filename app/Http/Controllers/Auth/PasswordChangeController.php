<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordChangeRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function showChangeForm(): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user && ($user->hasRole('Student') || $user->role === 'student') && ! $user->must_change_password) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Students cannot change their password. Only the school administrator can change student passwords.');
        }

        return view('Auth.AuthPasswordChange');
    }

    public function update(PasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->hasRole('Student') || $user->role === 'student') && ! $user->must_change_password) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Students cannot change their password. Only the school administrator can change student passwords.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);
        AuditLogger::log('password_changed', $user);

        return redirect()->route($user->dashboardRoute())
            ->with('success', 'Your password has been updated successfully.');
    }
}
