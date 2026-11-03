<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Account;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    use ResolvesCompany;

    private const TYPES = ['customers', 'suppliers', 'items', 'opening_balances', 'historical_invoices', 'historical_bills'];

    public function index()
    {
        $company = $this->company();
        return view('imports.index', compact('company'), ['batches' => ImportBatch::where('company_id', $company->id)->latest()->take(12)->get()]);
    }

    public function template(string $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        $rows = match ($type) {
            'customers' => [["name","company_name","email","phone","vat_number","billing_address","payment_terms_days"],["Ahmed Al Balushi","Al Noor Trading","accounts@example.com","+968 90000000","OM12345678","Muscat, Oman","30"]],
            'suppliers' => [["name","company_name","email","phone","vat_number","billing_address","payment_terms_days"],["Mohammed Al Hinai","Oman Supplies LLC","sales@example.com","+968 90000000","OM12345678","Muscat, Oman","30"]],
            'opening_balances' => [["entry_date","account_code","account_name","account_type","debit","credit"],["2026-01-01","1000","Cash and bank","asset","15000.000","0.000"],["2026-01-01","3000","Owner equity","equity","0.000","15000.000"]],
            'historical_invoices' => [["invoice_number","invoice_date","due_date","customer_email","customer_name","line_description","quantity","unit","unit_price","discount_percentage","vat_rate","payment_amount","payment_date","payment_method","payment_reference"],["QB-INV-1001","2025-12-15","2026-01-14","accounts@example.com","Al Noor Trading","Consulting service","1","service","100.000","0","5","105.000","2025-12-15","bank_transfer","QB-RCPT-1001"]],
            'historical_bills' => [["bill_number","bill_date","due_date","supplier_email","supplier_name","line_description","quantity","unit","unit_cost","vat_rate","payment_amount","payment_date","payment_method","payment_reference"],["QB-BILL-1001","2025-12-15","2026-01-14","purchases@example.com","Oman Supplies LLC","Office supplies","1","unit","100.000","5","105.000","2025-12-15","bank_transfer","QB-PAY-1001"]],
            default => [["sku","name","type","unit","cost_price","selling_price","vat_rate","reorder_level","description"],["ITEM-001","Example Item","product","pcs","1.500","2.000","5","10","Imported item"]],
        };
        return response()->streamDownload(function () use ($rows) { $out=fopen('php://output','w'); foreach ($rows as $row) fputcsv($out,$row); fclose($out); }, 'powerbooks_'.$type.'_template.csv', ['Content-Type'=>'text/csv']);
    }

    public function store(Request $request)
    {
        $data=$request->validate(['type'=>'required|in:customers,suppliers,items,opening_balances,historical_invoices,historical_bills','file'=>'required|file|mimes:csv,txt|max:5120']);
        $company=$this->company(); $handle=fopen($request->file('file')->getRealPath(),'r'); $headers=fgetcsv($handle);
        if (!$headers) { fclose($handle); return back()->withErrors(['file'=>'The CSV file is empty.'])->withInput(); }
        $headers=array_map(fn($header)=>strtolower(trim((string)$header)),$headers); fclose($handle);
        return match($data['type']) {
            'opening_balances' => $this->importOpeningBalances($request,$company,$headers),
            'historical_invoices' => $this->importHistoricalInvoices($request,$company,$headers),
            'historical_bills' => $this->importHistoricalBills($request,$company,$headers),
            default => $this->importMasterData($request,$company,$headers,$data['type']),
        };
    }

    private function importMasterData(Request $request, $company, array $headers, string $type)
    {
        $required=$type==='items'?['name','type']:['name'];
        if (array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks '.$type.' template headings. Download the template and try again.'])->withInput();
        $result=$this->readRows($request,$headers); $imported=0;$skipped=0;$errors=[];
        DB::transaction(function () use ($company,$type,$result,&$imported,&$skipped,&$errors) {
            foreach ($result as $index=>$values) { try {
                if ($type==='customers') { $name=$values['name']??''; if($name==='') throw new \RuntimeException('Name is required.'); Customer::firstOrCreate(['company_id'=>$company->id,'email'=>($values['email'] ?? '')?:null,'name'=>$name],['company_name'=>($values['company_name'] ?? '')?:null,'phone'=>($values['phone'] ?? '')?:null,'vat_number'=>($values['vat_number'] ?? '')?:null,'billing_address'=>($values['billing_address'] ?? '')?:null,'payment_terms_days'=>is_numeric($values['payment_terms_days']??null)?max(0,(int)$values['payment_terms_days']):30]); }
                elseif ($type==='suppliers') { $name=$values['name']??''; if($name==='') throw new \RuntimeException('Name is required.'); Supplier::firstOrCreate(['company_id'=>$company->id,'email'=>($values['email'] ?? '')?:null,'name'=>$name],['company_name'=>($values['company_name'] ?? '')?:null,'phone'=>($values['phone'] ?? '')?:null,'vat_number'=>($values['vat_number'] ?? '')?:null,'billing_address'=>($values['billing_address'] ?? '')?:null,'payment_terms_days'=>is_numeric($values['payment_terms_days']??null)?max(0,(int)$values['payment_terms_days']):30]); }
                else { $name=$values['name']??'';$itemType=$values['type']??''; if($name===''||!in_array($itemType,['product','service'],true)) throw new \RuntimeException('Name and valid type (product or service) are required.'); $sku=($values['sku'] ?? '')?:null; if($sku && Product::where('company_id',$company->id)->where('sku',$sku)->exists()) throw new \RuntimeException('SKU already exists.'); Product::create(['company_id'=>$company->id,'sku'=>$sku,'name'=>$name,'type'=>$itemType,'unit'=>($values['unit'] ?? '')?:'Unit','cost_price'=>is_numeric($values['cost_price']??null)?$values['cost_price']:0,'selling_price'=>is_numeric($values['selling_price']??null)?$values['selling_price']:0,'vat_rate'=>is_numeric($values['vat_rate']??null)?$values['vat_rate']:5,'reorder_level'=>is_numeric($values['reorder_level']??null)?$values['reorder_level']:0,'description'=>($values['description'] ?? '')?:null]); }
                $imported++;
            } catch (\Throwable $e) { $skipped++; if(count($errors)<25)$errors[]='Row '.($index+2).': '.$e->getMessage(); } }
        });
        $batch=$this->recordBatch($request,$company,$type,count($result),$imported,$skipped,$errors);
        return redirect()->route('imports.index')->with('success',"Import completed: {$batch->imported_count} added, {$batch->skipped_count} skipped.");
    }

    private function importOpeningBalances(Request $request, $company, array $headers)
    {
        $required=['entry_date','account_code','account_name','account_type','debit','credit'];
        if(array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks opening balance template headings. Download the template and try again.'])->withInput();
        $rows=$this->readRows($request,$headers); $errors=[];
        foreach($rows as $index=>$row) { $debit=(float)($row['debit']??0);$credit=(float)($row['credit']??0); if(empty($row['entry_date'])||empty($row['account_code'])||empty($row['account_name'])||!in_array($row['account_type']??'', ['asset','liability','equity','income','expense'],true)||$debit<0||$credit<0||($debit>0&&$credit>0)||($debit<=0&&$credit<=0)) $errors[]='Row '.($index+2).': enter a date, account code, account name, valid account type, and a debit or credit amount.'; }
        $dates=collect($rows)->pluck('entry_date')->unique(); $debits=round(collect($rows)->sum(fn($r)=>(float)$r['debit']),3); $credits=round(collect($rows)->sum(fn($r)=>(float)$r['credit']),3);
        if($errors||count($rows)<2||$dates->count()!==1||abs($debits-$credits)>.0005) return back()->withErrors(['file'=>$errors[0]??'Opening balances need at least two rows, one entry date, and equal total debits and credits.'])->withInput();
        if(JournalEntry::where('company_id',$company->id)->where('source_type','opening_balance_import')->exists()) return back()->withErrors(['file'=>'An opening-balance import already exists for this business. Ask an administrator or accountant to review the ledger before replacing it.'])->withInput();
        $batch=DB::transaction(function() use($request,$company,$rows,$debits) { $batch=$this->recordBatch($request,$company,'opening_balances',count($rows),count($rows),0,[]); $entry=JournalEntry::create(['company_id'=>$company->id,'source_type'=>'opening_balance_import','source_id'=>$batch->id,'entry_number'=>'QB-OPEN-'.$batch->id,'entry_date'=>$rows[0]['entry_date'],'reference'=>'QuickBooks opening balance import','memo'=>'Imported opening trial balance. Total debits and credits: OMR '.number_format($debits,3),'created_by'=>$request->user()->id]); foreach($rows as $row) { $account=Account::firstOrCreate(['company_id'=>$company->id,'code'=>$row['account_code']],['name'=>$row['account_name'],'type'=>$row['account_type'],'is_active'=>true]); if($account->type!==$row['account_type']) throw new \RuntimeException('Account '.$row['account_code'].' already exists with a different account type.'); $entry->lines()->create(['account_id'=>$account->id,'description'=>'QuickBooks opening balance','debit'=>$row['debit'],'credit'=>$row['credit']]); } return $batch; });
        return redirect()->route('imports.index')->with('success',"Opening balances imported as a balanced journal: {$batch->imported_count} accounts.");
    }

    private function importHistoricalInvoices(Request $request, $company, array $headers)
    {
        $required=['invoice_number','invoice_date','customer_name','line_description','quantity','unit','unit_price','discount_percentage','vat_rate'];
        if(array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks historical invoice template headings. Download the template and try again.'])->withInput();
        return $this->importHistoricalDocuments($request,$company,$headers,'historical_invoices','invoice_number',fn($number,$rows)=>$this->createHistoricalInvoice($company,$number,$rows,$request));
    }

    private function importHistoricalBills(Request $request, $company, array $headers)
    {
        $required=['bill_number','bill_date','supplier_name','line_description','quantity','unit','unit_cost','vat_rate'];
        if(array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks historical supplier bill template headings. Download the template and try again.'])->withInput();
        return $this->importHistoricalDocuments($request,$company,$headers,'historical_bills','bill_number',fn($number,$rows)=>$this->createHistoricalBill($company,$number,$rows,$request));
    }

    private function importHistoricalDocuments(Request $request,$company,array $headers,string $type,string $numberColumn,callable $create)
    {
        $groups=[]; foreach($this->readRows($request,$headers) as $index=>$row) { $number=$row[$numberColumn]??''; $groups[$number!==''?$number:'__invalid_'.($index+2)][]=$row; }
        $imported=0;$skipped=0;$errors=[];
        foreach($groups as $number=>$rows) { try { DB::transaction(fn()=>$create($number,$rows)); $imported++; } catch(\Throwable $e) { $skipped++;if(count($errors)<25)$errors[]=ucfirst(str_replace('_',' ',rtrim($type,'s'))).' '.$number.': '.$e->getMessage(); } }
        $batch=$this->recordBatch($request,$company,$type,collect($groups)->sum(fn($rows)=>count($rows)),$imported,$skipped,$errors);
        return redirect()->route('imports.index')->with('success',ucfirst(str_replace('_',' ',rtrim($type,'s')))." import completed: {$imported} added, {$skipped} skipped.");
    }

    private function createHistoricalInvoice($company,string $number,array $rows,Request $request): void
    {
        $first=$rows[0]; if(str_starts_with($number,'__invalid_')||empty($first['invoice_date'])||empty($first['customer_name'])) throw new \RuntimeException('invoice number, date, and customer name are required.'); if(Invoice::where('company_id',$company->id)->where('invoice_number',$number)->exists()) throw new \RuntimeException('invoice number already exists.');
        $customer=!empty($first['customer_email'] ?? null)?Customer::where('company_id',$company->id)->where('email',$first['customer_email'])->first():null; $customer??=Customer::where('company_id',$company->id)->where('name',$first['customer_name'])->first(); $customer??=Customer::create(['company_id'=>$company->id,'name'=>$first['customer_name'],'email'=>($first['customer_email'] ?? '')?:null]);
        $prepared=[];$subtotal=0;$discountTotal=0;$vatTotal=0; foreach($rows as $row) { $qty=(float)($row['quantity']??0);$price=(float)($row['unit_price']??0);$discount=(float)($row['discount_percentage']??0);$vat=(float)($row['vat_rate']??0); if(empty($row['line_description'])||$qty<=0||$price<0||$discount<0||$discount>100||$vat<0||$vat>100) throw new \RuntimeException('line data is invalid.'); $gross=$qty*$price;$discountAmount=$gross*$discount/100;$lineSubtotal=round($gross-$discountAmount,3);$lineVat=round($lineSubtotal*$vat/100,3);$subtotal+=$gross;$discountTotal+=$discountAmount;$vatTotal+=$lineVat;$prepared[]=[$row,$lineSubtotal,$lineVat]; }
        $total=round($subtotal-$discountTotal+$vatTotal,3); $payments=$this->validatedPayments($rows,$total); $invoice=Invoice::create(['company_id'=>$company->id,'customer_id'=>$customer->id,'invoice_number'=>$number,'invoice_date'=>$first['invoice_date'],'due_date'=>($first['due_date'] ?? '')?:$first['invoice_date'],'status'=>'issued','currency'=>$company->currency?:'OMR','subtotal'=>$subtotal,'discount_total'=>$discountTotal,'vat_total'=>$vatTotal,'total'=>$total,'paid_total'=>0,'balance_due'=>$total,'notes'=>'Imported QuickBooks historical invoice']); foreach($prepared as $index=>[$row,$lineSubtotal,$lineVat]) $invoice->items()->create(['sort_order'=>$index,'description'=>$row['line_description'],'quantity'=>$row['quantity'],'unit'=>$row['unit'],'unit_price'=>$row['unit_price'],'discount_percentage'=>($row['discount_percentage'] ?? '')?:0,'vat_rate'=>($row['vat_rate'] ?? '')?:0,'line_subtotal'=>$lineSubtotal,'line_vat'=>$lineVat,'line_total'=>$lineSubtotal+$lineVat]); app(AccountingPostingService::class)->postInvoice($invoice); foreach($payments as $paymentData) { $payment=$invoice->payments()->create($paymentData+['company_id'=>$company->id,'notes'=>'Imported QuickBooks payment']); app(AccountingPostingService::class)->postCustomerPayment($payment); } $paid=collect($payments)->sum('amount'); $invoice->update(['paid_total'=>$paid,'balance_due'=>round($total-$paid,3),'status'=>$paid>=$total-.0005?'paid':($paid>0?'partially_paid':'issued')]);
    }

    private function createHistoricalBill($company,string $number,array $rows,Request $request): void
    {
        $first=$rows[0]; if(str_starts_with($number,'__invalid_')||empty($first['bill_date'])||empty($first['supplier_name'])) throw new \RuntimeException('bill number, date, and supplier name are required.'); if(PurchaseBill::where('company_id',$company->id)->where('bill_number',$number)->exists()) throw new \RuntimeException('bill number already exists.');
        $supplier=!empty($first['supplier_email'] ?? null)?Supplier::where('company_id',$company->id)->where('email',$first['supplier_email'])->first():null; $supplier??=Supplier::where('company_id',$company->id)->where('name',$first['supplier_name'])->first(); $supplier??=Supplier::create(['company_id'=>$company->id,'name'=>$first['supplier_name'],'email'=>($first['supplier_email'] ?? '')?:null]);
        $prepared=[];$subtotal=0;$vatTotal=0; foreach($rows as $row) { $qty=(float)($row['quantity']??0);$cost=(float)($row['unit_cost']??0);$vat=(float)($row['vat_rate']??0); if(empty($row['line_description'])||$qty<=0||$cost<0||$vat<0||$vat>100) throw new \RuntimeException('line data is invalid.'); $lineSubtotal=round($qty*$cost,3);$lineVat=round($lineSubtotal*$vat/100,3);$subtotal+=$lineSubtotal;$vatTotal+=$lineVat;$prepared[]=[$row,$lineSubtotal,$lineVat]; }
        $total=round($subtotal+$vatTotal,3); $payments=$this->validatedPayments($rows,$total); $bill=PurchaseBill::create(['company_id'=>$company->id,'supplier_id'=>$supplier->id,'bill_number'=>$number,'bill_date'=>$first['bill_date'],'due_date'=>($first['due_date'] ?? '')?:$first['bill_date'],'status'=>'received','currency'=>$company->currency?:'OMR','subtotal'=>$subtotal,'vat_total'=>$vatTotal,'total'=>$total,'paid_total'=>0,'balance_due'=>$total,'notes'=>'Imported QuickBooks historical supplier bill']); foreach($prepared as $index=>[$row,$lineSubtotal,$lineVat]) $bill->items()->create(['sort_order'=>$index,'description'=>$row['line_description'],'quantity'=>$row['quantity'],'unit'=>$row['unit'],'unit_cost'=>$row['unit_cost'],'vat_rate'=>($row['vat_rate'] ?? '')?:0,'line_subtotal'=>$lineSubtotal,'line_vat'=>$lineVat,'line_total'=>$lineSubtotal+$lineVat]); app(AccountingPostingService::class)->postPurchaseBill($bill); foreach($payments as $paymentData) { $payment=$bill->payments()->create($paymentData+['company_id'=>$company->id,'notes'=>'Imported QuickBooks supplier payment']); app(AccountingPostingService::class)->postSupplierPayment($payment); } $paid=collect($payments)->sum('amount'); $bill->update(['paid_total'=>$paid,'balance_due'=>round($total-$paid,3),'status'=>$paid>=$total-.0005?'paid':($paid>0?'partially_paid':'received')]);
    }

    private function validatedPayments(array $rows,float $total): array
    {
        $payments=[];$seen=[];$paid=0; foreach($rows as $row) { $amount=(float)($row['payment_amount']??0); if($amount<=0) continue; $key=implode('|',[$amount,$row['payment_date']??'', $row['payment_reference']??'']); if(isset($seen[$key])) continue; if(empty($row['payment_date'])||$paid+$amount>$total+.0005) throw new \RuntimeException('payment data is invalid or exceeds the document total.'); $payments[]=['payment_date'=>$row['payment_date'],'amount'=>$amount,'method'=>in_array($row['payment_method']??'', ['cash','bank_transfer','card','cheque','other'],true)?$row['payment_method']:'bank_transfer','reference'=>($row['payment_reference'] ?? '')?:null]; $paid+=$amount;$seen[$key]=true; } return $payments;
    }

    private function readRows(Request $request,array $headers): array
    {
        $handle=fopen($request->file('file')->getRealPath(),'r'); fgetcsv($handle); $rows=[]; while(($row=fgetcsv($handle))!==false) { if(count($row)===1&&trim((string)$row[0])==='') continue; $row=array_pad($row,count($headers),''); $rows[]=array_combine($headers,array_map('trim',array_slice($row,0,count($headers)))); } fclose($handle); return $rows;
    }

    private function recordBatch(Request $request,$company,string $type,int $read,int $imported,int $skipped,array $errors): ImportBatch
    {
        return ImportBatch::create(['company_id'=>$company->id,'created_by'=>$request->user()->id,'type'=>$type,'original_filename'=>$request->file('file')->getClientOriginalName(),'status'=>$skipped?'completed_with_errors':'completed','rows_read'=>$read,'imported_count'=>$imported,'skipped_count'=>$skipped,'errors'=>$errors]);
    }
}
