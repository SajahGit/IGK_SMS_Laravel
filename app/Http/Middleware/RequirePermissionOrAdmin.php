<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermissionOrAdmin
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        $requiredPermissions = explode('|', $permissions);

        abort_unless(
            $user && ($user->isSuperAdmin() || collect($requiredPermissions)->contains(fn ($permission) => $user->can($permission))),
            403
        );

        return $next($request);
    }
}
