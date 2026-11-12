<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Student\PayHereController;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();

    return $user instanceof User
        ? redirect()->route($user->dashboardRoute())
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

Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallenge'])
    ->middleware('throttle:two-factor')
    ->name('two-factor.challenge');
Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])
    ->middleware('throttle:two-factor');
Route::get('/two-factor/setup', [TwoFactorController::class, 'showSetup'])
    ->name('two-factor.setup');
Route::post('/two-factor/setup', [TwoFactorController::class, 'confirmSetup'])
    ->middleware('throttle:two-factor');
Route::post('/two-factor/complete', [TwoFactorController::class, 'completeSetup'])
    ->name('two-factor.complete');
Route::post('/two-factor/cancel', [TwoFactorController::class, 'cancel'])
    ->name('two-factor.cancel');

Route::post('/payments/payhere/notify', [PayHereController::class, 'notify'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('payhere.notify');

Route::middleware('auth')->group(function () {
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.recovery-codes.regenerate');
    Route::get('/account/api-tokens', [ApiTokenController::class, 'index'])->name('account.api-tokens.index');
    Route::post('/account/api-tokens', [ApiTokenController::class, 'store'])->middleware('throttle:10,1')->name('account.api-tokens.store');
    Route::delete('/account/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('account.api-tokens.destroy');
    Route::get('/password/change', [PasswordChangeController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('messages.store');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/replies', [MessageController::class, 'storeReply'])
        ->middleware('throttle:30,1')
        ->name('messages.replies.store');
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

Route::middleware(['auth', 'role:admin,librarian'])->prefix('library')->name('library.')
    ->group(base_path('routes/library.php'));

Route::middleware(['auth', 'role:admin,accountant'])->prefix('procurement')->name('procurement.')
    ->group(base_path('routes/procurement.php'));

Route::middleware(['auth', 'role:admin,teacher,registrar'])->prefix('reports')->name('reports.')
    ->group(base_path('routes/reports.php'));
