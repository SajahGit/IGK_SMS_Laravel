<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if (! $request->routeIs('password.change', 'password.change.update', 'logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your password before proceeding.');
            }
        }

        return $next($request);
    }
}
