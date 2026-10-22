<?php

use App\Http\Controllers\Guardian\ChildAttendanceController;
use App\Http\Controllers\Guardian\ChildResultController;
use App\Http\Controllers\Guardian\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/children/{student}/attendance', [ChildAttendanceController::class, 'show'])->name('attendance');
Route::get('/children/{student}/results', [ChildResultController::class, 'show'])->name('results');
