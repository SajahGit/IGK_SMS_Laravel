<?php

use App\Http\Controllers\Api\V1\BiometricEventController;
use App\Http\Controllers\Api\V1\StudentDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/biometric/events', [BiometricEventController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.v1.biometric.events.store');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/me', function (Request $request) {
        abort_unless($request->user()->tokenCan('profile:read'), 403);

        return response()->json(['data' => [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role,
        ]]);
    })->name('me');

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/attendance', [StudentDataController::class, 'attendance'])->name('attendance');
        Route::get('/results', [StudentDataController::class, 'results'])->name('results');
        Route::get('/invoices', [StudentDataController::class, 'invoices'])->name('invoices');
    });
});
