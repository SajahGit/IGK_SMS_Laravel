<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: role:admin  or  role:admin,teacher
     *
     * Checks spatie roles first, then falls back to the legacy `role` column.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        $roleColumn = strtolower($user->role);
        $roleColumn = match ($roleColumn) {
            'institution admin', 'super admin', 'super-admin' => 'admin',
            default => $roleColumn,
        };
        $normalizedRoles = array_map('strtolower', $roles);

        if (! $user->hasAnyRole($roles) && ! in_array($roleColumn, $normalizedRoles, true)) {
            abort(403, 'You are not allowed to open this page.');
        }

        return $next($request);
    }
}
