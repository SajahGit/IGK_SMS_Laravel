<?php

use App\Http\Controllers\Guardian\ChildAttendanceController;
use App\Http\Controllers\Guardian\ChildResultController;
use App\Http\Controllers\Guardian\DashboardController;
use App\Http\Controllers\Guardian\InvoiceController;
use App\Http\Controllers\Guardian\ChildHostelController;
use App\Http\Controllers\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/children/{student}/attendance', [ChildAttendanceController::class, 'show'])->name('attendance');
Route::get('/children/{student}/results', [ChildResultController::class, 'show'])->name('results');
Route::get('/children/{student}/report-card', [ChildResultController::class, 'reportCard'])->name('report-card');
Route::get('/children/{student}/report-card/pdf', [ChildResultController::class, 'downloadReportCard'])->name('report-card.pdf');
Route::get('/children/{student}/transcript', [TranscriptController::class, 'guardian'])->name('children.transcript');
Route::get('/children/{student}/transcript/pdf', [TranscriptController::class, 'guardianPdf'])->name('children.transcript.pdf');
Route::get('/children/{student}/hostel', [ChildHostelController::class, 'show'])->name('children.hostel');
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
