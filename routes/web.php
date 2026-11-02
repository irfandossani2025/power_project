<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::get('/purchases', [PurchaseBillController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseBillController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseBillController::class, 'store'])->name('purchases.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/movements', [InventoryController::class, 'store'])->name('inventory.movements.store');
    Route::resource('quotations', QuotationController::class);
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/quotations/{quotation}/invoice', [InvoiceController::class, 'fromQuotation'])->name('quotations.invoice');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
