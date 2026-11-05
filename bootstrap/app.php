<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RequirePermissionOrAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'force_password_change' => ForcePasswordChange::class,
            'permission' => RequirePermissionOrAdmin::class,
        ]);
        $middleware->appendToGroup('web', [
            ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
