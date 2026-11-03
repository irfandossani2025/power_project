<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\StockMovement;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use ResolvesCompany;

    public function index() { $company = $this->company(); return view('invoices.index', compact('company'), ['invoices' => Invoice::where('company_id', $company->id)->with('customer')->latest('invoice_date')->paginate(12)]); }

    public function show(Invoice $invoice) { $this->authorizeInvoice($invoice); return view('invoices.show', ['company' => $this->company(), 'invoice' => $invoice->load('company', 'customer', 'items', 'payments')]); }

    public function fromQuotation(Quotation $quotation)
    {
        $company = $this->company(); abort_unless($quotation->company_id === $company->id, 404); $quotation->load('items', 'customer');
        $invoice = DB::transaction(function () use ($company, $quotation) {
            $existing = Invoice::where('quotation_id', $quotation->id)->first(); if ($existing) return $existing;
            if ($quotation->status !== 'accepted') {
                abort(422, 'Only accepted quotations can be converted to an invoice.');
            }
            $invoice = Invoice::create(['company_id'=>$company->id,'customer_id'=>$quotation->customer_id,'quotation_id'=>$quotation->id,'invoice_number'=>$this->nextNumber($company->id),'invoice_date'=>now(),'due_date'=>now()->addDays($quotation->customer->payment_terms_days),'status'=>'issued','currency'=>$quotation->currency,'subtotal'=>$quotation->subtotal,'discount_total'=>$quotation->discount_total,'vat_total'=>$quotation->vat_total,'total'=>$quotation->total,'paid_total'=>0,'balance_due'=>$quotation->total,'notes'=>$quotation->notes,'terms'=>$quotation->terms]);
            foreach ($quotation->items as $item) { $invoice->items()->create($item->only(['product_id','sort_order','description','quantity','unit','unit_price','discount_percentage','vat_rate','line_subtotal','line_vat','line_total'])); if ($item->product_id && $item->product?->type === 'product') StockMovement::create(['company_id'=>$company->id,'product_id'=>$item->product_id,'type'=>'sale','quantity'=>-$item->quantity,'unit_cost'=>$item->product->cost_price,'movement_date'=>now(),'reference'=>$invoice->invoice_number,'notes'=>'Issued on sales invoice '.$invoice->invoice_number,'created_by'=>auth()->id()]); }
            $quotation->update(['status'=>'converted']); app(AccountingPostingService::class)->postInvoice($invoice); return $invoice;
        });
        return redirect()->route('invoices.show', $invoice)->with('success', 'Sales invoice '.$invoice->invoice_number.' is ready.');
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $data = $request->validate(['payment_date'=>'required|date','amount'=>'required|numeric|gt:0|lte:'.$invoice->balance_due,'method'=>'required|in:cash,bank_transfer,card,cheque,other','reference'=>'nullable|string|max:100','notes'=>'nullable|string']);
        DB::transaction(function () use ($invoice, $data) { $payment=$invoice->payments()->create($data + ['company_id'=>$invoice->company_id]); $paid=(float)$invoice->payments()->sum('amount'); $balance=round(max(0,(float)$invoice->total-$paid),3); $invoice->update(['paid_total'=>$paid,'balance_due'=>$balance,'status'=>$balance <= 0.000?'paid':'partially_paid']); app(AccountingPostingService::class)->postCustomerPayment($payment); });
        return back()->with('success', 'Payment recorded and invoice balance updated.');
    }

    private function nextNumber(int $companyId): string { return 'INV-'.now()->format('Y').'-'.str_pad((string)(Invoice::where('company_id',$companyId)->count()+1),4,'0',STR_PAD_LEFT); }
    private function authorizeInvoice(Invoice $invoice): void { abort_unless($invoice->company_id===$this->company()->id,404); }
}
