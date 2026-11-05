<?php

use App\Http\Controllers\Admin\ClassSubjectController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GuardianController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('students', StudentController::class)->except('show');
Route::resource('teachers', TeacherController::class)->except('show');
Route::resource('guardians', GuardianController::class)->except('show');
Route::resource('classes', SchoolClassController::class)->except('show')
    ->parameters(['classes' => 'schoolClass']);
Route::resource('subjects', SubjectController::class)->except('show');

Route::get('/class-subjects', [ClassSubjectController::class, 'index'])->name('class-subjects.index');
Route::post('/class-subjects', [ClassSubjectController::class, 'store'])->name('class-subjects.store');
Route::delete('/class-subjects/{classSubject}', [ClassSubjectController::class, 'destroy'])->name('class-subjects.destroy');
