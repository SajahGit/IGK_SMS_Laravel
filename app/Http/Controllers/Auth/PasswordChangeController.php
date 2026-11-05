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
    public function showChangeForm(): View
    {
        return view('Auth.AuthPasswordChange');
    }

    public function update(PasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);
        AuditLogger::log('password_changed', $user);

        return redirect()->route($user->dashboardRoute())
            ->with('success', 'Your password has been updated successfully.');
    }
}
