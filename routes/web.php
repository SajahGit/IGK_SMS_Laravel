<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// ---- login / logout (accounts are created by the admin, there is no public register page) ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// After login everybody lands here and is sent to their own dashboard.
Route::get('/dashboard', function () {
    $name = auth()->user()->dashboardRoute();

    abort_unless(Route::has($name), 403, 'Your module is not installed in this project yet. Paste your module code and refresh.');

    return redirect()->route($name);
})->middleware(['auth', 'role'])->name('dashboard');

// Each module has its own routes file inside routes/Modules/
// A missing file is skipped, so the project also runs before every module is pasted in.
foreach (['Admin', 'ParentPortal', 'Teacher', 'Student', 'Reports'] as $module) {
    $file = __DIR__.'/Modules/'.$module.'.php';
    if (file_exists($file)) {
        require $file;
    }
}
