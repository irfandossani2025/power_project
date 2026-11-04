<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Expense;
use App\Models\Project;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ResolvesCompany;

    public const CATEGORIES = ['Rent & utilities', 'Transport & fuel', 'Salaries & wages', 'Office supplies', 'Marketing', 'Professional services', 'Travel', 'Repairs & maintenance', 'Bank charges', 'Other'];

    public function index() { $company=$this->company(); return view('expenses.index', compact('company'), ['expenses'=>Expense::where('company_id',$company->id)->with('project')->latest('expense_date')->paginate(15)]); }
    public function create() { return view('expenses.form', ['company'=>$this->company(),'expense'=>new Expense(['expense_date'=>now(),'category'=>'Other','payment_method'=>'cash','vat_rate'=>0]),'categories'=>self::CATEGORIES,'projects'=>$this->projects()]); }
    public function store(Request $request) { $expense=new Expense($this->validated($request)); $expense->company_id=$this->company()->id; $expense->created_by=$request->user()->id; $this->calculate($expense); DB::transaction(function () use ($expense) { $expense->save(); app(AccountingPostingService::class)->postExpense($expense); }); return redirect()->route('expenses.index')->with('success','Expense recorded successfully.'); }
    public function edit(Expense $expense) { $this->authorizeExpense($expense); return view('expenses.form',['company'=>$this->company(),'expense'=>$expense,'categories'=>self::CATEGORIES,'projects'=>$this->projects()]); }
    public function update(Request $request, Expense $expense) { $this->authorizeExpense($expense); $expense->fill($this->validated($request)); $this->calculate($expense); DB::transaction(function () use ($expense) { $expense->save(); app(AccountingPostingService::class)->postExpense($expense); }); return redirect()->route('expenses.index')->with('success','Expense updated successfully.'); }
    public function destroy(Expense $expense) { $this->authorizeExpense($expense); DB::transaction(function () use ($expense) { app(AccountingPostingService::class)->remove('expense', $expense->id); $expense->delete(); }); return redirect()->route('expenses.index')->with('success','Expense removed.'); }
    private function validated(Request $request): array { $data=$request->validate(['project_id'=>'nullable|integer','expense_date'=>'required|date','category'=>'required|string|max:100','payee'=>'nullable|string|max:191','reference'=>'nullable|string|max:100','description'=>'required|string|max:2000','payment_method'=>'required|in:cash,bank_transfer,card,cheque,other','amount_before_vat'=>'required|numeric|min:0','vat_rate'=>'required|numeric|min:0|max:100','notes'=>'nullable|string|max:2000']); if(!empty($data['project_id']) && !Project::where('company_id',$this->company()->id)->whereKey($data['project_id'])->exists()) abort(422); return $data; }
    private function projects() { return Project::where('company_id',$this->company()->id)->whereNotIn('status',['completed','cancelled'])->orderBy('name')->get(); }
    private function calculate(Expense $expense): void { $expense->vat_amount=round((float)$expense->amount_before_vat*(float)$expense->vat_rate/100,3); $expense->total=round((float)$expense->amount_before_vat+(float)$expense->vat_amount,3); }
    private function authorizeExpense(Expense $expense): void { abort_unless($expense->company_id===$this->company()->id,404); }
}
