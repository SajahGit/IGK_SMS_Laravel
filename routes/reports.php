<?php

use App\Http\Controllers\Report\AttendanceReportController;
use App\Http\Controllers\Report\AttendanceSummaryPerStudentReportController;
use App\Http\Controllers\Report\ClassAttendanceSummaryReportController;
use App\Http\Controllers\Report\ClassResultSummaryReportController;
use App\Http\Controllers\Report\DefaultersReportController;
use App\Http\Controllers\Report\EnrollmentRegisterReportController;
use App\Http\Controllers\Report\FeeCollectionReportController;
use App\Http\Controllers\Report\ResultReportController;
use App\Http\Controllers\Report\StudentFinancialLedgerReportController;
use App\Http\Controllers\Report\StudentReportController;
use App\Http\Controllers\Report\SubjectPerformanceReportController;
use Illuminate\Support\Facades\Route;

Route::get('/students', [StudentReportController::class, 'index'])->name('students');
Route::get('/students/csv', [StudentReportController::class, 'csv'])->middleware('throttle:exports')->name('students.csv');

Route::get('/enrollment', [EnrollmentRegisterReportController::class, 'index'])->name('enrollment');
Route::get('/enrollment/csv', [EnrollmentRegisterReportController::class, 'csv'])->middleware('throttle:exports')->name('enrollment.csv');

Route::get('/attendance', [AttendanceReportController::class, 'index'])->name('attendance');
Route::get('/attendance/csv', [AttendanceReportController::class, 'csv'])->middleware('throttle:exports')->name('attendance.csv');

Route::get('/attendance-summary', [AttendanceSummaryPerStudentReportController::class, 'index'])->name('attendance-summary');
Route::get('/attendance-summary/csv', [AttendanceSummaryPerStudentReportController::class, 'csv'])->middleware('throttle:exports')->name('attendance-summary.csv');

Route::get('/class-attendance-summary', [ClassAttendanceSummaryReportController::class, 'index'])->name('class-attendance');
Route::get('/class-attendance-summary/csv', [ClassAttendanceSummaryReportController::class, 'csv'])->middleware('throttle:exports')->name('class-attendance.csv');

Route::get('/results', [ResultReportController::class, 'index'])->name('results');
Route::get('/results/csv', [ResultReportController::class, 'csv'])->middleware('throttle:exports')->name('results.csv');

Route::get('/class-result', [ClassResultSummaryReportController::class, 'index'])->name('class-result');
Route::get('/class-result/csv', [ClassResultSummaryReportController::class, 'csv'])->middleware('throttle:exports')->name('class-result.csv');

Route::get('/subject-performance', [SubjectPerformanceReportController::class, 'index'])->name('subject-performance');
Route::get('/subject-performance/csv', [SubjectPerformanceReportController::class, 'csv'])->middleware('throttle:exports')->name('subject-performance.csv');

Route::get('/fee-collection', [FeeCollectionReportController::class, 'index'])->name('fee-collection');
Route::get('/fee-collection/csv', [FeeCollectionReportController::class, 'csv'])->middleware('throttle:exports')->name('fee-collection.csv');

Route::get('/defaulters', [DefaultersReportController::class, 'index'])->name('defaulters');
Route::get('/defaulters/csv', [DefaultersReportController::class, 'csv'])->middleware('throttle:exports')->name('defaulters.csv');

Route::get('/student-ledger', [StudentFinancialLedgerReportController::class, 'index'])->name('student-ledger');
Route::get('/student-ledger/csv', [StudentFinancialLedgerReportController::class, 'csv'])->middleware('throttle:exports')->name('student-ledger.csv');
