<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Usage: ->middleware('role:admin')  or  ->middleware('role:admin,teacher')
     * With no roles it only checks that the account is active.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your account is deactivated. Contact the admin.']);
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to open this page.');
        }

        return $next($request);
    }
}
