<?php

use App\Http\Controllers\Accountant\DashboardController;
use App\Http\Controllers\Accountant\FeeStructureController;
use App\Http\Controllers\Accountant\InvoiceController;
use App\Http\Controllers\Accountant\PaymentController;
use App\Http\Controllers\InstitutionAdmin\SavedFilterController;
use App\Http\Controllers\Report\DefaultersReportController;
use App\Http\Controllers\Report\FeeCollectionReportController;
use App\Http\Controllers\Report\ReportPdfController;
use App\Http\Controllers\Report\StudentFinancialLedgerReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('fee-structures', FeeStructureController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
Route::post('/invoices/filters', [SavedFilterController::class, 'storeInvoices'])->name('invoices.filters.store');
Route::delete('/invoices/filters/{savedFilter}', [SavedFilterController::class, 'destroyInvoices'])->name('invoices.filters.destroy');
Route::post('/invoices/issue-drafts', [InvoiceController::class, 'issueDrafts'])->name('invoices.issue-drafts');
Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
Route::post('/fee-structures/{feeStructure}/items', [FeeStructureController::class, 'storeItem'])->name('fee-structures.items.store');
Route::delete('/fee-items/{feeItem}', [FeeStructureController::class, 'destroyItem'])->name('fee-items.destroy');
Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
Route::get('/receipts/{payment}', [PaymentController::class, 'receipt'])->name('receipt.show');
Route::get('/receipts/{payment}/pdf', [PaymentController::class, 'receiptPdf'])->name('receipt.pdf');

Route::get('/reports/fee-collection', [FeeCollectionReportController::class, 'index'])->name('reports.fee-collection');
Route::get('/reports/fee-collection/csv', [FeeCollectionReportController::class, 'csv'])->middleware('throttle:exports')->name('reports.fee-collection.csv');
Route::get('/reports/defaulters', [DefaultersReportController::class, 'index'])->name('reports.defaulters');
Route::get('/reports/defaulters/csv', [DefaultersReportController::class, 'csv'])->middleware('throttle:exports')->name('reports.defaulters.csv');
Route::get('/reports/student-ledger', [StudentFinancialLedgerReportController::class, 'index'])->name('reports.student-ledger');
Route::get('/reports/student-ledger/csv', [StudentFinancialLedgerReportController::class, 'csv'])->middleware('throttle:exports')->name('reports.student-ledger.csv');
Route::get('/reports/{report}/pdf', [ReportPdfController::class, 'download'])
    ->whereIn('report', ['fee-collection', 'defaulters', 'student-ledger'])
    ->middleware('throttle:exports')
    ->name('reports.pdf');
