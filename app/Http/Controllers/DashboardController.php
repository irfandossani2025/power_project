<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Models\Product;
use App\Models\Quotation;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function __invoke()
    {
        $company = $this->company();
        $quotations = Quotation::where('company_id', $company->id)->with('customer')->latest('quotation_date');
        $monthTotal = (clone $quotations)->whereBetween('quotation_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
        $openTotal = (clone $quotations)->whereIn('status', ['draft', 'sent'])->sum('total');
        $invoices = Invoice::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void']);
        $bills = PurchaseBill::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void']);
        $lowStock = Product::where('company_id', $company->id)->where('type', 'product')->where('reorder_level', '>', 0)->withSum('stockMovements as stock_on_hand', 'quantity')->get()->filter(fn ($product) => (float) ($product->stock_on_hand ?? 0) <= (float) $product->reorder_level);

        return view('dashboard', [
            'company' => $company,
            'monthTotal' => $monthTotal,
            'openTotal' => $openTotal,
            'salesMonth' => (clone $invoices)->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'receivables' => (clone $invoices)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'),
            'payables' => (clone $bills)->whereIn('status', ['received', 'partially_paid'])->sum('balance_due'),
            'lowStock' => $lowStock,
            'recentInvoices' => $invoices->with('customer')->latest('invoice_date')->take(5)->get(),
            'customers' => Customer::where('company_id', $company->id)->where('is_active', true)->count(),
            'products' => Product::where('company_id', $company->id)->where('is_active', true)->count(),
            'recentQuotations' => $quotations->take(6)->get(),
        ]);
    }
}
