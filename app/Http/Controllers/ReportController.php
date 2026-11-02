<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Product;
use App\Models\PurchaseBill;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request)
    {
        $company = $this->company();
        $from = ($request->date('from') ?? now()->startOfMonth())->startOfDay();
        $to = ($request->date('to') ?? now())->endOfDay();
        $invoices = Invoice::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void'])->whereBetween('invoice_date', [$from, $to]);
        $bills = PurchaseBill::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void'])->whereBetween('bill_date', [$from, $to]);
        $expenses = Expense::where('company_id', $company->id)->whereBetween('expense_date', [$from, $to]);
        $lowStock = Product::where('company_id', $company->id)->where('type', 'product')->where('reorder_level', '>', 0)->withSum('stockMovements as stock_on_hand', 'quantity')->get()->filter(fn ($product) => (float) ($product->stock_on_hand ?? 0) <= (float) $product->reorder_level);
        return view('reports.index', [
            'company' => $company, 'from' => $from, 'to' => $to,
            'salesTotal' => (clone $invoices)->sum('total'), 'salesVat' => (clone $invoices)->sum('vat_total'),
            'receivables' => Invoice::where('company_id', $company->id)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'),
            'purchasesTotal' => (clone $bills)->sum('total'), 'purchaseVat' => (clone $bills)->sum('vat_total'),
            'expensesTotal' => (clone $expenses)->sum('total'), 'expenseVat' => (clone $expenses)->sum('vat_amount'),
            'payables' => PurchaseBill::where('company_id', $company->id)->whereIn('status', ['received', 'partially_paid'])->sum('balance_due'),
            'lowStock' => $lowStock, 'recentInvoices' => $invoices->with('customer')->latest('invoice_date')->take(8)->get(), 'recentBills' => $bills->with('supplier')->latest('bill_date')->take(8)->get(),
        ]);
    }
}
