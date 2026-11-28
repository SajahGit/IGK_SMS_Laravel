<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RequirePermissionOrAdmin;
use App\Http\Middleware\ResolveBranchContext;
use App\Http\Middleware\SetCurrentAcademicContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            ResolveBranchContext::class,
            ForcePasswordChange::class,
            SetCurrentAcademicContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $handleExpired = function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh and try again.',
                ], 419);
            }

            $target = auth()->check() ? url()->previous() : route('login');

            return redirect()->to($target)
                ->withInput($request->except('password', '_token'))
                ->with('error', 'Your session expired. Please try again.');
        };

        $exceptions->render(fn (TokenMismatchException $e, Request $request) => $handleExpired($request));
        $exceptions->render(function (HttpException $e, Request $request) use ($handleExpired) {
            if ($e->getStatusCode() === 419) {
                return $handleExpired($request);
            }

            return null;
        });
    })->create();
