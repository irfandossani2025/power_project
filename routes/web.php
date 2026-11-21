<?php

use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceBuddyController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/finance-buddy', [FinanceBuddyController::class, 'index'])->name('finance-buddy.index');
    Route::post('/finance-buddy/ask', [FinanceBuddyController::class, 'ask'])->name('finance-buddy.ask');

    Route::middleware('role:admin,accountant,sales')->group(function () {
        Route::resource('customers', CustomerController::class)->except('show');
        Route::resource('quotations', QuotationController::class);
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/quotations/{quotation}/invoice', [InvoiceController::class, 'fromQuotation'])->name('quotations.invoice');
    });

    Route::middleware('role:admin,accountant,inventory')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::get('/purchases', [PurchaseBillController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseBillController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseBillController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseBillController::class, 'show'])->name('purchases.show');
        Route::post('/inventory/movements', [InventoryController::class, 'store'])->name('inventory.movements.store');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::resource('vehicles', VehicleController::class)->except('show', 'destroy');
    });

    Route::middleware('role:admin,accountant,sales,inventory')->group(function () {
        Route::resource('projects', ProjectController::class);
    });

    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('employees', EmployeeController::class)->except('show', 'destroy');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::patch('/payroll/{payroll}/entries/{entry}', [PayrollController::class, 'updateEntry'])->name('payroll.entries.update');
        Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::get('/payroll/{payroll}/entries/{entry}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::get('/payroll/{payroll}/bank-muscat-export', [PayrollController::class, 'export'])->name('payroll.export');
        Route::get('/employees/{employee}/compensation', [PayrollController::class, 'compensation'])->name('payroll.compensation');
        Route::patch('/employees/{employee}/compensation', [PayrollController::class, 'updateCompensation'])->name('payroll.compensation.update');
        Route::resource('expenses', ExpenseController::class)->except('show');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::post('/purchases/{purchase}/payments', [PurchaseBillController::class, 'recordPayment'])->name('purchases.payments.store');
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('/imports/templates/{type}', [ImportController::class, 'template'])->name('imports.template');
        Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    });
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::post('/ledger/accounts', [LedgerController::class, 'storeAccount'])->name('ledger.accounts.store');
        Route::post('/ledger/entries', [LedgerController::class, 'storeEntry'])->name('ledger.entries.store');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/payroll/{payroll}/entries/{entry}/payslip.pdf', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        Route::get('/payroll/{payroll}/payslips.pdf', [PayrollController::class, 'downloadAllPayslips'])->name('payroll.payslips.download-all');
        Route::get('/settings/payroll', [PayrollController::class, 'settings'])->name('payroll.settings');
        Route::patch('/settings/payroll', [PayrollController::class, 'updateSettings'])->name('payroll.settings.update');
        Route::get('/settings/finance-buddy', [FinanceBuddyController::class, 'settings'])->name('settings.finance-buddy.edit');
        Route::patch('/settings/finance-buddy', [FinanceBuddyController::class, 'updateSettings'])->name('settings.finance-buddy.update');
        Route::get('/settings/company', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
        Route::patch('/settings/company', [CompanySettingsController::class, 'update'])->name('settings.company.update');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
