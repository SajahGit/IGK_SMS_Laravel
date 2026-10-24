<?php

use App\Http\Controllers\Report\AttendanceReportController;
use App\Http\Controllers\Report\ResultReportController;
use App\Http\Controllers\Report\StudentReportController;
use Illuminate\Support\Facades\Route;

Route::get('/students', [StudentReportController::class, 'index'])->name('students');
Route::get('/students/csv', [StudentReportController::class, 'csv'])->name('students.csv');

Route::get('/attendance', [AttendanceReportController::class, 'index'])->name('attendance');
Route::get('/attendance/csv', [AttendanceReportController::class, 'csv'])->name('attendance.csv');

Route::get('/results', [ResultReportController::class, 'index'])->name('results');
Route::get('/results/csv', [ResultReportController::class, 'csv'])->name('results.csv');
