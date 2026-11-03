<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PurchaseBill;
use App\Models\SupplierPayment;
use App\Models\Payment;

class AccountingPostingService
{
    /** Ensure the standard operating chart exists before a system posting. */
    public function ensureAccounts(int $companyId): void
    {
        foreach ([
            ['1000', 'Cash and bank', 'asset'], ['1100', 'Accounts receivable', 'asset'],
            ['1200', 'Inventory', 'asset'], ['1300', 'VAT input receivable', 'asset'],
            ['2000', 'Accounts payable', 'liability'], ['2100', 'VAT payable', 'liability'],
            ['3000', 'Owner equity', 'equity'], ['4000', 'Sales revenue', 'income'],
            ['5000', 'Cost of sales', 'expense'], ['6000', 'Operating expenses', 'expense'],
        ] as [$code, $name, $type]) {
            Account::firstOrCreate(['company_id' => $companyId, 'code' => $code], ['name' => $name, 'type' => $type, 'is_active' => true]);
        }
    }

    public function postInvoice(Invoice $invoice): void
    {
        $lines = [
            ['1100', $invoice->total, 0], ['4000', 0, (float) $invoice->subtotal - (float) $invoice->discount_total], ['2100', 0, $invoice->vat_total],
        ];
        $costOfGoods = $invoice->items()->with('product')->get()->sum(fn ($item) => $item->product?->type === 'product' ? (float) $item->quantity * (float) $item->product->cost_price : 0);
        if ($costOfGoods > 0) { $lines[] = ['5000', $costOfGoods, 0]; $lines[] = ['1200', 0, $costOfGoods]; }
        $this->post($invoice->company_id, 'invoice', $invoice->id, 'INV-'.$invoice->invoice_number, $invoice->invoice_date, 'Invoice '.$invoice->invoice_number, $lines);
    }

    public function postCustomerPayment(Payment $payment): void
    {
        $this->post($payment->company_id, 'customer_payment', $payment->id, 'PAY-'.$payment->id, $payment->payment_date, 'Customer payment'.($payment->reference ? ' · '.$payment->reference : ''), [
            ['1000', $payment->amount, 0], ['1100', 0, $payment->amount],
        ]);
    }

    public function postPurchaseBill(PurchaseBill $bill): void
    {
        $items = $bill->items()->with('product')->get();
        $inventory = (float) $items->sum(fn ($item) => $item->product?->type === 'product' ? $item->line_subtotal : 0);
        $directCost = (float) $bill->subtotal - $inventory;
        $this->post($bill->company_id, 'purchase_bill', $bill->id, 'BILL-'.$bill->bill_number, $bill->bill_date, 'Supplier bill '.$bill->bill_number, [
            ['1200', $inventory, 0], ['5000', $directCost, 0], ['1300', $bill->vat_total, 0], ['2000', 0, $bill->total],
        ]);
    }

    public function postSupplierPayment(SupplierPayment $payment): void
    {
        $this->post($payment->company_id, 'supplier_payment', $payment->id, 'SPAY-'.$payment->id, $payment->payment_date, 'Supplier payment'.($payment->reference ? ' · '.$payment->reference : ''), [
            ['2000', $payment->amount, 0], ['1000', 0, $payment->amount],
        ]);
    }

    public function postExpense(Expense $expense): void
    {
        $this->post($expense->company_id, 'expense', $expense->id, 'EXP-'.$expense->id, $expense->expense_date, $expense->description, [
            ['6000', $expense->amount_before_vat, 0], ['1300', $expense->vat_amount, 0], ['1000', 0, $expense->total],
        ]);
    }

    public function remove(string $sourceType, int $sourceId): void
    {
        JournalEntry::where('source_type', $sourceType)->where('source_id', $sourceId)->delete();
    }

    private function post(int $companyId, string $sourceType, int $sourceId, string $reference, mixed $date, string $memo, array $lines): void
    {
        $this->ensureAccounts($companyId);
        $accounts = Account::where('company_id', $companyId)->whereIn('code', collect($lines)->pluck(0)->unique())->get()->keyBy('code');
        $entry = JournalEntry::firstOrNew(['company_id' => $companyId, 'source_type' => $sourceType, 'source_id' => $sourceId]);
        $entry->fill(['entry_number' => 'AUTO-'.strtoupper(str_replace('_', '-', $sourceType)).'-'.$sourceId, 'entry_date' => $date, 'reference' => $reference, 'memo' => $memo, 'created_by' => auth()->id()]);
        $entry->save();
        $entry->lines()->delete();
        foreach ($lines as [$code, $debit, $credit]) {
            if ((float) $debit > 0 || (float) $credit > 0) $entry->lines()->create(['account_id' => $accounts[$code]->id, 'description' => $memo, 'debit' => round((float) $debit, 3), 'credit' => round((float) $credit, 3)]);
        }
    }
}
