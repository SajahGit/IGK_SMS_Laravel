<?php

use App\Http\Controllers\InstitutionAdmin\AccountController;
use App\Http\Controllers\InstitutionAdmin\AccountingReportController;
use App\Http\Controllers\InstitutionAdmin\BranchController;
use App\Http\Controllers\InstitutionAdmin\CashflowEntryController;
use App\Http\Controllers\InstitutionAdmin\JournalEntryController;
use Illuminate\Support\Facades\Route;

// Loaded from routes/modules/admin.php (prefix admin., role admin/registrar); restricted to admins here.
Route::middleware('role:admin')->group(function () {
    Route::post('branch-context', [BranchController::class, 'switchContext'])->name('branch-context');
    Route::resource('branches', BranchController::class)->except(['show']);
    Route::resource('accounts', AccountController::class)->except(['show']);

    Route::post('journals/auto-post', [JournalEntryController::class, 'sync'])->name('journals.sync');
    Route::resource('journals', JournalEntryController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->parameters(['journals' => 'journal']);
    Route::get('accounting/transactions/create', [CashflowEntryController::class, 'create'])->name('accounting.transactions.create');
    Route::post('accounting/transactions', [CashflowEntryController::class, 'store'])->name('accounting.transactions.store');

    Route::get('accounting/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('accounting/ledger', [AccountingReportController::class, 'ledger'])->name('accounting.ledger');
    Route::get('accounting/income-expense', [AccountingReportController::class, 'incomeExpense'])->name('accounting.income-expense');
    Route::get('accounting/branch-summary', [AccountingReportController::class, 'branchSummary'])->name('accounting.branch-summary');
});
