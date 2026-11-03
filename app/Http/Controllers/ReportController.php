<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\Account;
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
        $accounts = Account::where('company_id', $company->id)->with(['journalLines.entry' => fn ($query) => $query->where('entry_date', '<=', $to)])->orderBy('code')->get();
        $periodAccounts = $accounts->map(function (Account $account) use ($from, $to) {
            $lines = $account->journalLines->filter(fn ($line) => $line->entry && $line->entry->entry_date->between($from, $to));
            return ['account' => $account, 'debit' => (float) $lines->sum('debit'), 'credit' => (float) $lines->sum('credit')];
        });
        $incomeStatement = [
            'income' => $periodAccounts->filter(fn ($row) => $row['account']->type === 'income')->map(fn ($row) => $row + ['amount' => $row['credit'] - $row['debit']])->filter(fn ($row) => abs($row['amount']) > .0005),
            'expenses' => $periodAccounts->filter(fn ($row) => $row['account']->type === 'expense')->map(fn ($row) => $row + ['amount' => $row['debit'] - $row['credit']])->filter(fn ($row) => abs($row['amount']) > .0005),
        ];
        $incomeStatement['incomeTotal'] = $incomeStatement['income']->sum('amount');
        $incomeStatement['expenseTotal'] = $incomeStatement['expenses']->sum('amount');
        $incomeStatement['netProfit'] = $incomeStatement['incomeTotal'] - $incomeStatement['expenseTotal'];
        $profitToDate = $accounts->filter(fn ($account) => $account->type === 'income')->sum(fn ($account) => (float) $account->journalLines->sum('credit') - (float) $account->journalLines->sum('debit')) - $accounts->filter(fn ($account) => $account->type === 'expense')->sum(fn ($account) => (float) $account->journalLines->sum('debit') - (float) $account->journalLines->sum('credit'));
        $balanceSheet = [
            'assets' => $accounts->filter(fn ($account) => $account->type === 'asset')->map(fn ($account) => ['account' => $account, 'amount' => (float) $account->journalLines->sum('debit') - (float) $account->journalLines->sum('credit')])->filter(fn ($row) => abs($row['amount']) > .0005),
            'liabilities' => $accounts->filter(fn ($account) => $account->type === 'liability')->map(fn ($account) => ['account' => $account, 'amount' => (float) $account->journalLines->sum('credit') - (float) $account->journalLines->sum('debit')])->filter(fn ($row) => abs($row['amount']) > .0005),
            'equity' => $accounts->filter(fn ($account) => $account->type === 'equity')->map(fn ($account) => ['account' => $account, 'amount' => (float) $account->journalLines->sum('credit') - (float) $account->journalLines->sum('debit')])->filter(fn ($row) => abs($row['amount']) > .0005),
        ];
        $balanceSheet['assetTotal'] = $balanceSheet['assets']->sum('amount');
        $balanceSheet['liabilityTotal'] = $balanceSheet['liabilities']->sum('amount');
        $balanceSheet['equityTotal'] = $balanceSheet['equity']->sum('amount') + $profitToDate;
        $lowStock = Product::where('company_id', $company->id)->where('type', 'product')->where('reorder_level', '>', 0)->withSum('stockMovements as stock_on_hand', 'quantity')->get()->filter(fn ($product) => (float) ($product->stock_on_hand ?? 0) <= (float) $product->reorder_level);
        return view('reports.index', [
            'company' => $company, 'from' => $from, 'to' => $to,
            'incomeStatement' => $incomeStatement, 'balanceSheet' => $balanceSheet,
            'salesTotal' => (clone $invoices)->sum('total'), 'salesVat' => (clone $invoices)->sum('vat_total'),
            'receivables' => Invoice::where('company_id', $company->id)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'),
            'purchasesTotal' => (clone $bills)->sum('total'), 'purchaseVat' => (clone $bills)->sum('vat_total'),
            'expensesTotal' => (clone $expenses)->sum('total'), 'expenseVat' => (clone $expenses)->sum('vat_amount'),
            'payables' => PurchaseBill::where('company_id', $company->id)->whereIn('status', ['received', 'partially_paid'])->sum('balance_due'),
            'lowStock' => $lowStock, 'recentInvoices' => $invoices->with('customer')->latest('invoice_date')->take(8)->get(), 'recentBills' => $bills->with('supplier')->latest('bill_date')->take(8)->get(),
        ]);
    }
}
