<?php

use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers.read')->name('suppliers.index');
Route::get('suppliers/create', [SupplierController::class, 'create'])->middleware('permission:suppliers.create')->name('suppliers.create');
Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create')->name('suppliers.store');
Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->middleware('permission:suppliers.update')->name('suppliers.edit');
Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.update')->name('suppliers.update');
Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete')->name('suppliers.destroy');

Route::get('orders', [PurchaseOrderController::class, 'index'])->middleware('permission:procurement.read')->name('orders.index');
Route::get('orders/create', [PurchaseOrderController::class, 'create'])->middleware('permission:procurement.create')->name('orders.create');
Route::post('orders', [PurchaseOrderController::class, 'store'])->middleware('permission:procurement.create')->name('orders.store');
Route::get('orders/{order}', [PurchaseOrderController::class, 'show'])->middleware('permission:procurement.read')->name('orders.show');
Route::get('orders/{order}/edit', [PurchaseOrderController::class, 'edit'])->middleware('permission:procurement.update')->name('orders.edit');
Route::put('orders/{order}', [PurchaseOrderController::class, 'update'])->middleware('permission:procurement.update')->name('orders.update');
Route::delete('orders/{order}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:procurement.delete')->name('orders.destroy');
Route::post('orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->middleware('permission:procurement.update')->name('orders.submit');
Route::post('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->middleware('permission:procurement.approve')->name('orders.approve');
Route::post('orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:procurement.update')->name('orders.receive');
Route::post('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:procurement.update')->name('orders.cancel');
