<?php

use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\Library\LibraryMasterController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [LibraryController::class, 'index'])
    ->middleware('permission:library.read')
    ->name('dashboard');
Route::post('/books', [LibraryController::class, 'storeBook'])
    ->middleware('permission:library.create')
    ->name('books.store');
Route::get('/books/{book}/edit', [LibraryController::class, 'edit'])
    ->middleware('permission:library.update')
    ->name('books.edit');
Route::put('/books/{book}', [LibraryController::class, 'update'])
    ->middleware('permission:library.update')
    ->name('books.update');
Route::delete('/books/{book}', [LibraryController::class, 'destroy'])
    ->middleware('permission:library.delete')
    ->name('books.destroy');
Route::post('/books/{book}/copies', [LibraryController::class, 'storeCopy'])
    ->middleware('permission:library.create')
    ->name('books.copies.store');
Route::delete('/copies/{copy}', [LibraryController::class, 'destroyCopy'])
    ->middleware('permission:library.delete')
    ->name('copies.destroy');
Route::post('/loans', [LibraryController::class, 'borrow'])
    ->middleware('permission:library.create')
    ->name('loans.store');
Route::post('/loans/{loan}/return', [LibraryController::class, 'returnBook'])
    ->middleware('permission:library.update')
    ->name('loans.return');
Route::post('/fines/{fine}/pay', [LibraryController::class, 'payFine'])
    ->middleware('permission:library.update')
    ->name('fines.pay');

Route::get('/authors', [LibraryMasterController::class, 'authors'])->middleware('permission:library.read')->name('authors.index');
Route::post('/authors', [LibraryMasterController::class, 'authorStore'])->middleware('permission:library.create')->name('authors.store');
Route::put('/authors/{author}', [LibraryMasterController::class, 'authorUpdate'])->middleware('permission:library.update')->name('authors.update');
Route::delete('/authors/{author}', [LibraryMasterController::class, 'authorDestroy'])->middleware('permission:library.delete')->name('authors.destroy');

Route::get('/categories', [LibraryMasterController::class, 'categories'])->middleware('permission:library.read')->name('categories.index');
Route::post('/categories', [LibraryMasterController::class, 'categoryStore'])->middleware('permission:library.create')->name('categories.store');
Route::put('/categories/{category}', [LibraryMasterController::class, 'categoryUpdate'])->middleware('permission:library.update')->name('categories.update');
Route::delete('/categories/{category}', [LibraryMasterController::class, 'categoryDestroy'])->middleware('permission:library.delete')->name('categories.destroy');

Route::get('/copies', [LibraryMasterController::class, 'copies'])->middleware('permission:library.read')->name('copies.index');
Route::put('/copies/{copy}', [LibraryMasterController::class, 'copyUpdate'])->middleware('permission:library.update')->name('copies.update');
Route::get('/loans', [LibraryMasterController::class, 'loans'])->middleware('permission:library.read')->name('loans.index');
Route::put('/loans/{loan}', [LibraryMasterController::class, 'loanUpdate'])->middleware('permission:library.update')->name('loans.update');
Route::delete('/loans/{loan}', [LibraryMasterController::class, 'loanDestroy'])->middleware('permission:library.delete')->name('loans.destroy');
Route::get('/fines', [LibraryMasterController::class, 'fines'])->middleware('permission:library.read')->name('fines.index');
Route::put('/fines/{fine}', [LibraryMasterController::class, 'fineUpdate'])->middleware('permission:library.update')->name('fines.update');
Route::delete('/fines/{fine}', [LibraryMasterController::class, 'fineDestroy'])->middleware('permission:library.delete')->name('fines.destroy');
