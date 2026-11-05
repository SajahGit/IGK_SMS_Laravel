<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.attempt');

    // Password reset
    Route::get('/password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.forgot');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:3,60')
        ->name('password.email');
    Route::get('/password/reset', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// ---- Each module has its own route file (one owner per file) ----
Route::middleware(['auth', 'role:admin,registrar'])->prefix('admin')->name('admin.')
    ->group(base_path('routes/modules/admin.php'));

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')
    ->group(base_path('routes/teacher.php'));

Route::middleware(['auth', 'role:accountant'])->prefix('accountant')->name('accountant.')
    ->group(base_path('routes/accountant.php'));

Route::middleware(['auth', 'role:registrar'])->prefix('registrar')->name('registrar.')
    ->group(base_path('routes/registrar.php'));

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')
    ->group(base_path('routes/student.php'));

Route::middleware(['auth', 'role:parent'])->prefix('guardian')->name('guardian.')
    ->group(base_path('routes/guardian.php'));

Route::middleware(['auth', 'role:admin,teacher,registrar'])->prefix('reports')->name('reports.')
    ->group(base_path('routes/reports.php'));
