<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    use ResolvesCompany;

    public function index() { $company=$this->company(); return view('imports.index', compact('company'), ['batches'=>ImportBatch::where('company_id',$company->id)->latest()->take(12)->get()]); }

    public function template(string $type)
    {
        abort_unless(in_array($type,['customers','suppliers','items','opening_balances'],true),404);
        $rows = match($type) { 'customers' => [["name","company_name","email","phone","vat_number","billing_address","payment_terms_days"],["Ahmed Al Balushi","Al Noor Trading","accounts@example.com","+968 90000000","OM12345678","Muscat, Oman","30"]], 'suppliers' => [["name","company_name","email","phone","vat_number","billing_address","payment_terms_days"],["Mohammed Al Hinai","Oman Supplies LLC","sales@example.com","+968 90000000","OM12345678","Muscat, Oman","30"]], 'opening_balances' => [["entry_date","account_code","account_name","account_type","debit","credit"],["2026-01-01","1000","Cash and bank","asset","15000.000","0.000"],["2026-01-01","3000","Owner equity","equity","0.000","15000.000"]], default => [["sku","name","type","unit","cost_price","selling_price","vat_rate","reorder_level","description"],["ITEM-001","Example Item","product","pcs","1.500","2.000","5","10","Imported item"]] };
        $filename='powerbooks_'.$type.'_template.csv';
        return response()->streamDownload(function() use($rows) { $out=fopen('php://output','w'); foreach($rows as $row) fputcsv($out,$row); fclose($out); },$filename,['Content-Type'=>'text/csv']);
    }

    public function store(Request $request)
    {
        $data=$request->validate(['type'=>'required|in:customers,suppliers,items,opening_balances','file'=>'required|file|mimes:csv,txt|max:5120']); $company=$this->company();
        $handle=fopen($request->file('file')->getRealPath(),'r'); $headers=fgetcsv($handle); if(!$headers) return back()->withErrors(['file'=>'The CSV file is empty.'])->withInput();
        $headers=array_map(fn($header)=>strtolower(trim((string)$header)),$headers); $required=$data['type']==='items'?['name','type']:['name'];
        if($data['type']==='opening_balances') { fclose($handle); return $this->importOpeningBalances($request,$company,$headers); }
        if(array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks '.$data['type'].' template headings. Download the template and try again.'])->withInput();
        $batch=DB::transaction(function() use($handle,$headers,$data,$company,$request) { $read=0;$imported=0;$skipped=0;$errors=[]; while(($row=fgetcsv($handle))!==false){$read++; if(count($row)===1 && trim((string)$row[0])==='')continue; $row=array_pad($row,count($headers),''); $values=array_combine($headers,array_map('trim',array_slice($row,0,count($headers)))); try { if($data['type']==='customers') {$name=$values['name']??''; if($name==='')throw new \RuntimeException('Name is required.'); Customer::firstOrCreate(['company_id'=>$company->id,'email'=>$values['email']?:null,'name'=>$name],['company_name'=>$values['company_name']?:null,'phone'=>$values['phone']?:null,'vat_number'=>$values['vat_number']?:null,'billing_address'=>$values['billing_address']?:null,'payment_terms_days'=>is_numeric($values['payment_terms_days']??null)?max(0,(int)$values['payment_terms_days']):30]);} elseif($data['type']==='suppliers') {$name=$values['name']??''; if($name==='')throw new \RuntimeException('Name is required.'); Supplier::firstOrCreate(['company_id'=>$company->id,'email'=>$values['email']?:null,'name'=>$name],['company_name'=>$values['company_name']?:null,'phone'=>$values['phone']?:null,'vat_number'=>$values['vat_number']?:null,'billing_address'=>$values['billing_address']?:null,'payment_terms_days'=>is_numeric($values['payment_terms_days']??null)?max(0,(int)$values['payment_terms_days']):30]);} else {$name=$values['name']??'';$type=$values['type']??''; if($name===''||!in_array($type,['product','service'],true))throw new \RuntimeException('Name and valid type (product or service) are required.'); $sku=$values['sku']?:null; if($sku && Product::where('company_id',$company->id)->where('sku',$sku)->exists())throw new \RuntimeException('SKU already exists.'); Product::create(['company_id'=>$company->id,'sku'=>$sku,'name'=>$name,'type'=>$type,'unit'=>$values['unit']?:'Unit','cost_price'=>is_numeric($values['cost_price']??null)?$values['cost_price']:0,'selling_price'=>is_numeric($values['selling_price']??null)?$values['selling_price']:0,'vat_rate'=>is_numeric($values['vat_rate']??null)?$values['vat_rate']:5,'reorder_level'=>is_numeric($values['reorder_level']??null)?$values['reorder_level']:0,'description'=>$values['description']?:null]);} $imported++;} catch(\Throwable $exception) {$skipped++; if(count($errors)<25)$errors[]='Row '.($read+1).': '.$exception->getMessage();} }
            return ImportBatch::create(['company_id'=>$company->id,'created_by'=>$request->user()->id,'type'=>$data['type'],'original_filename'=>$request->file('file')->getClientOriginalName(),'status'=>$skipped?'completed_with_errors':'completed','rows_read'=>$read,'imported_count'=>$imported,'skipped_count'=>$skipped,'errors'=>$errors]);
        }); fclose($handle); return redirect()->route('imports.index')->with('success',"Import completed: {$batch->imported_count} added, {$batch->skipped_count} skipped.");
    }

    private function importOpeningBalances(Request $request, $company, array $headers)
    {
        $required=['entry_date','account_code','account_name','account_type','debit','credit'];
        if(array_diff($required,$headers)) return back()->withErrors(['file'=>'This CSV does not use the PowerBooks opening balance template headings. Download the template and try again.'])->withInput();
        $handle=fopen($request->file('file')->getRealPath(),'r'); fgetcsv($handle); $rows=[]; $errors=[]; $line=1;
        while(($row=fgetcsv($handle))!==false) { $line++; if(count($row)===1 && trim((string)$row[0])==='') continue; $values=array_combine($headers,array_map('trim',array_slice(array_pad($row,count($headers),''),0,count($headers)))); $debit=(float)($values['debit'] ?? 0); $credit=(float)($values['credit'] ?? 0); if(empty($values['entry_date']) || empty($values['account_code']) || empty($values['account_name']) || !in_array($values['account_type'] ?? '',['asset','liability','equity','income','expense'],true) || $debit<0 || $credit<0 || ($debit>0 && $credit>0) || ($debit<=0 && $credit<=0)) {$errors[]='Row '.$line.': enter a date, account code, account name, valid account type, and a debit or credit amount.'; continue;} $rows[]=$values+['debit'=>$debit,'credit'=>$credit]; }
        fclose($handle); $dates=collect($rows)->pluck('entry_date')->unique(); $debits=round(collect($rows)->sum('debit'),3); $credits=round(collect($rows)->sum('credit'),3);
        if($errors || count($rows)<2 || $dates->count()!==1 || abs($debits-$credits)>.0005) return back()->withErrors(['file'=>$errors[0] ?? 'Opening balances need at least two rows, one entry date, and equal total debits and credits.'])->withInput();
        $batch=DB::transaction(function() use($request,$company,$rows,$debits,$credits) { $batch=ImportBatch::create(['company_id'=>$company->id,'created_by'=>$request->user()->id,'type'=>'opening_balances','original_filename'=>$request->file('file')->getClientOriginalName(),'status'=>'completed','rows_read'=>count($rows),'imported_count'=>count($rows),'skipped_count'=>0,'errors'=>[]]); $entry=JournalEntry::create(['company_id'=>$company->id,'source_type'=>'opening_balance_import','source_id'=>$batch->id,'entry_number'=>'QB-OPEN-'.$batch->id,'entry_date'=>$rows[0]['entry_date'],'reference'=>'QuickBooks opening balance import','memo'=>'Imported opening trial balance. Total debits and credits: OMR '.number_format($debits,3),'created_by'=>$request->user()->id]); foreach($rows as $row) { $account=Account::firstOrCreate(['company_id'=>$company->id,'code'=>$row['account_code']],['name'=>$row['account_name'],'type'=>$row['account_type'],'is_active'=>true]); if($account->type!==$row['account_type']) throw new \RuntimeException('Account '.$row['account_code'].' already exists with a different account type.'); $entry->lines()->create(['account_id'=>$account->id,'description'=>'QuickBooks opening balance','debit'=>$row['debit'],'credit'=>$row['credit']]); } return $batch; });
        return redirect()->route('imports.index')->with('success',"Opening balances imported as a balanced journal: {$batch->imported_count} accounts.");
    }
}
