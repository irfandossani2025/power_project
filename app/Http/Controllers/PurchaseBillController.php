<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Project;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseBillController extends Controller
{
    use ResolvesCompany;

    public function index() { $company=$this->company(); return view('purchases.index', compact('company'), ['bills'=>PurchaseBill::where('company_id',$company->id)->with(['supplier','project'])->latest('bill_date')->paginate(12)]); }
    public function show(PurchaseBill $purchase) { $this->authorizeBill($purchase); return view('purchases.show', ['company'=>$this->company(), 'bill'=>$purchase->load('company','supplier','items.product','payments')]); }
    public function create() { $company=$this->company(); return view('purchases.form', compact('company'), ['suppliers'=>Supplier::where('company_id',$company->id)->where('is_active',true)->orderBy('company_name')->orderBy('name')->get(), 'products'=>Product::where('company_id',$company->id)->where('is_active',true)->orderBy('name')->get(), 'projects'=>Project::where('company_id',$company->id)->whereNotIn('status',['completed','cancelled'])->orderBy('name')->get()]); }
    public function store(Request $request)
    {
        $company=$this->company();
        $data=$request->validate(['supplier_id'=>'required|integer','project_id'=>'nullable|integer','bill_number'=>'required|string|max:100','bill_date'=>'required|date','due_date'=>'nullable|date|after_or_equal:bill_date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.product_id'=>'nullable|integer','items.*.description'=>'required|string|max:2000','items.*.quantity'=>'required|numeric|gt:0','items.*.unit'=>'required|string|max:20','items.*.unit_cost'=>'required|numeric|min:0','items.*.vat_rate'=>'required|numeric|min:0|max:100']);
        $supplier=Supplier::where('company_id',$company->id)->findOrFail($data['supplier_id']);
        if (!empty($data['project_id'])) Project::where('company_id',$company->id)->findOrFail($data['project_id']);
        $exists=PurchaseBill::where('company_id',$company->id)->where('bill_number',$data['bill_number'])->exists(); if($exists) return back()->withErrors(['bill_number'=>'This supplier bill number is already in use.'])->withInput();
        $bill=DB::transaction(function() use($company,$data,$supplier) {
            $subtotal=0; $vat=0; $prepared=[];
            foreach($data['items'] as $index=>$item) { $line=round((float)$item['quantity']*(float)$item['unit_cost'],3); $lineVat=round($line*(float)$item['vat_rate']/100,3); $subtotal+=$line; $vat+=$lineVat; $prepared[]=[$item,$line,$lineVat,$index]; }
            $total=round($subtotal+$vat,3);
            $bill=PurchaseBill::create(['company_id'=>$company->id,'supplier_id'=>$supplier->id,'project_id'=>$data['project_id'] ?? null,'bill_number'=>$data['bill_number'],'bill_date'=>$data['bill_date'],'due_date'=>$data['due_date'] ?? now()->parse($data['bill_date'])->addDays($supplier->payment_terms_days),'status'=>'received','currency'=>$company->currency ?: 'OMR','subtotal'=>$subtotal,'vat_total'=>$vat,'total'=>$total,'paid_total'=>0,'balance_due'=>$total,'notes'=>$data['notes'] ?? null]);
            foreach($prepared as [$item,$line,$lineVat,$index]) { $product=null; if(!empty($item['product_id'])) $product=Product::where('company_id',$company->id)->findOrFail($item['product_id']); $bill->items()->create(['product_id'=>$product?->id,'sort_order'=>$index,'description'=>$item['description'],'quantity'=>$item['quantity'],'unit'=>$item['unit'],'unit_cost'=>$item['unit_cost'],'vat_rate'=>$item['vat_rate'],'line_subtotal'=>$line,'line_vat'=>$lineVat,'line_total'=>$line+$lineVat]); if($product?->type==='product') StockMovement::create(['company_id'=>$company->id,'product_id'=>$product->id,'type'=>'receipt','quantity'=>$item['quantity'],'unit_cost'=>$item['unit_cost'],'movement_date'=>$data['bill_date'],'reference'=>$data['bill_number'],'notes'=>'Received from '.$supplier->name,'created_by'=>auth()->id()]); }
            app(AccountingPostingService::class)->postPurchaseBill($bill); return $bill;
        });
        return redirect()->route('purchases.index')->with('success', 'Purchase bill '.$bill->bill_number.' recorded. Stock quantities were updated for product lines.');
    }

    public function recordPayment(Request $request, PurchaseBill $purchase)
    {
        $this->authorizeBill($purchase);
        $data=$request->validate(['payment_date'=>'required|date','amount'=>'required|numeric|gt:0|lte:'.$purchase->balance_due,'method'=>'required|in:cash,bank_transfer,card,cheque,other','reference'=>'nullable|string|max:100','notes'=>'nullable|string']);
        DB::transaction(function () use ($purchase, $data) { $payment=$purchase->payments()->create($data+['company_id'=>$purchase->company_id]); $paid=(float)$purchase->payments()->sum('amount'); $balance=round(max(0,(float)$purchase->total-$paid),3); $purchase->update(['paid_total'=>$paid,'balance_due'=>$balance,'status'=>$balance <= .000?'paid':'partially_paid']); app(AccountingPostingService::class)->postSupplierPayment($payment); });
        return back()->with('success','Supplier payment recorded and bill balance updated.');
    }

    private function authorizeBill(PurchaseBill $bill): void { abort_unless($bill->company_id===$this->company()->id,404); }
}
