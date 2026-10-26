<?php

use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\ClassController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ExamController;
use App\Http\Controllers\Teacher\MarkController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
Route::get('/classes/{schoolClass}/students', [ClassController::class, 'students'])->name('classes.students');

Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');

Route::get('/exams/{exam}/marks', [MarkController::class, 'edit'])->name('marks.edit');
Route::post('/exams/{exam}/marks', [MarkController::class, 'update'])->name('marks.update');
Route::get('/exams/{exam}/results', [MarkController::class, 'show'])->name('marks.show');
