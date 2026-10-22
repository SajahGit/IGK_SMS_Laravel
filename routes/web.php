<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Each module has its own route file (one owner per file) ----
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')
    ->group(base_path('routes/admin.php'));

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')
    ->group(base_path('routes/teacher.php'));

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')
    ->group(base_path('routes/student.php'));

Route::middleware(['auth', 'role:parent'])->prefix('guardian')->name('guardian.')
    ->group(base_path('routes/guardian.php'));

Route::middleware(['auth', 'role:admin,teacher'])->prefix('reports')->name('reports.')
    ->group(base_path('routes/reports.php'));
