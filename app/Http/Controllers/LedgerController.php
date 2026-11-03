<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company=$this->company(); app(AccountingPostingService::class)->ensureAccounts($company->id);
        $accounts=Account::where('company_id',$company->id)->withSum('journalLines as debit_total','debit')->withSum('journalLines as credit_total','credit')->orderBy('code')->get();
        $trialBalance=$accounts->map(function (Account $account) { $debits=(float)($account->debit_total ?? 0); $credits=(float)($account->credit_total ?? 0); return ['account'=>$account,'debits'=>$debits,'credits'=>$credits,'net_debit'=>max(0,$debits-$credits),'net_credit'=>max(0,$credits-$debits)]; });
        return view('ledger.index',['company'=>$company,'accounts'=>$accounts,'trialBalance'=>$trialBalance,'trialDebitTotal'=>$trialBalance->sum('net_debit'),'trialCreditTotal'=>$trialBalance->sum('net_credit'),'entries'=>JournalEntry::where('company_id',$company->id)->with('lines.account')->latest('entry_date')->latest('id')->paginate(12)]);
    }

    public function storeAccount(Request $request)
    {
        $company=$this->company();
        $data=$request->validate(['code'=>'required|string|max:30','name'=>'required|string|max:191','type'=>'required|in:asset,liability,equity,income,expense']);
        if(Account::where('company_id',$company->id)->where('code',$data['code'])->exists()) return back()->withErrors(['code'=>'An account with this code already exists.'])->withInput();
        Account::create($data+['company_id'=>$company->id]);
        return back()->with('success','Account added to the chart of accounts.');
    }

    public function storeEntry(Request $request)
    {
        $company=$this->company(); app(AccountingPostingService::class)->ensureAccounts($company->id);
        $data=$request->validate(['entry_date'=>'required|date','reference'=>'nullable|string|max:100','memo'=>'nullable|string','lines'=>'required|array|min:2','lines.*.account_id'=>'required|integer','lines.*.description'=>'nullable|string|max:1000','lines.*.debit'=>'nullable|numeric|min:0','lines.*.credit'=>'nullable|numeric|min:0']);
        $debits=round(collect($data['lines'])->sum(fn($line)=>(float)($line['debit'] ?? 0)),3); $credits=round(collect($data['lines'])->sum(fn($line)=>(float)($line['credit'] ?? 0)),3);
        if($debits <= 0 || abs($debits-$credits) > .0005) return back()->withErrors(['lines'=>'Journal entries must balance: total debits and credits must be equal and greater than zero.'])->withInput();
        $accountIds=collect($data['lines'])->pluck('account_id')->unique();
        if(Account::where('company_id',$company->id)->whereIn('id',$accountIds)->count()!==$accountIds->count()) abort(422);
        DB::transaction(function() use($company,$data) { $number='JE-'.now()->format('Y').'-'.str_pad((string)(JournalEntry::where('company_id',$company->id)->count()+1),4,'0',STR_PAD_LEFT); $entry=JournalEntry::create(['company_id'=>$company->id,'entry_number'=>$number,'entry_date'=>$data['entry_date'],'reference'=>$data['reference'] ?? null,'memo'=>$data['memo'] ?? null,'created_by'=>auth()->id()]); foreach($data['lines'] as $line) { $debit=round((float)($line['debit'] ?? 0),3); $credit=round((float)($line['credit'] ?? 0),3); if($debit>0 || $credit>0) $entry->lines()->create(['account_id'=>$line['account_id'],'description'=>$line['description'] ?? null,'debit'=>$debit,'credit'=>$credit]); } });
        return back()->with('success','Balanced journal entry recorded.');
    }

}
