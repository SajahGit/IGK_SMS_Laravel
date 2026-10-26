<?php

use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
Route::get('/results', [ResultController::class, 'index'])->name('results');
