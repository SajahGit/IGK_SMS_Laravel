<?php

use App\Http\Controllers\Report\StudentFinancialLedgerReportController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\InvoiceController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/documents/{document}/download', [ProfileController::class, 'downloadDocument'])->name('documents.download');

Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
Route::get('/results', [ResultController::class, 'index'])->name('results');
Route::get('/report-card', [ResultController::class, 'reportCard'])->name('report-card');
Route::get('/report-card/pdf', [ResultController::class, 'downloadReportCard'])->name('report-card.pdf');
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::get('/ledger', [StudentFinancialLedgerReportController::class, 'index'])->name('ledger');
Route::get('/ledger/csv', [StudentFinancialLedgerReportController::class, 'csv'])->middleware('throttle:exports')->name('ledger.csv');
