<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth','active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::get('/purchases', [PurchaseBillController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseBillController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseBillController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseBillController::class, 'show'])->name('purchases.show');
    Route::post('/purchases/{purchase}/payments', [PurchaseBillController::class, 'recordPayment'])->name('purchases.payments.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::post('/ledger/accounts', [LedgerController::class, 'storeAccount'])->name('ledger.accounts.store');
        Route::post('/ledger/entries', [LedgerController::class, 'storeEntry'])->name('ledger.entries.store');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/templates/{type}', [ImportController::class, 'template'])->name('imports.template');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
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
