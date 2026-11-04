<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        return view('projects.index', compact('company'), ['projects' => Project::where('company_id', $company->id)->with('customer')->withSum('invoices as billed_total', 'total')->withSum('purchaseBills as purchase_total', 'total')->withSum('expenses as expense_total', 'total')->latest()->paginate(12)]);
    }

    public function create()
    {
        return view('projects.form', ['company' => $this->company(), 'project' => new Project(['status' => 'planning', 'start_date' => now(), 'budget' => 0]), 'customers' => $this->customers()]);
    }

    public function store(Request $request)
    {
        $project = new Project($this->validated($request));
        $project->company_id = $this->company()->id;
        $project->project_number = $this->nextNumber($project->company_id);
        $project->save();
        return redirect()->route('projects.show', $project)->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);
        $project->load(['customer', 'quotations' => fn ($q) => $q->latest('quotation_date'), 'invoices' => fn ($q) => $q->latest('invoice_date'), 'purchaseBills' => fn ($q) => $q->latest('bill_date'), 'expenses' => fn ($q) => $q->latest('expense_date')]);
        $quoted = (float) $project->quotations->whereIn('status', ['draft', 'sent', 'accepted', 'converted'])->sum('total');
        $billed = (float) $project->invoices->sum('total');
        $purchases = (float) $project->purchaseBills->sum('total');
        $expenses = (float) $project->expenses->sum('total');
        return view('projects.show', compact('project', 'quoted', 'billed', 'purchases', 'expenses') + ['company' => $this->company()]);
    }

    public function edit(Project $project)
    {
        $this->authorizeProject($project);
        return view('projects.form', ['company' => $this->company(), 'project' => $project, 'customers' => $this->customers()]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        $project->update($this->validated($request));
        return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeProject($project);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project removed. Linked documents have been retained and unlinked.');
    }

    private function customers() { return Customer::where('company_id', $this->company()->id)->where('is_active', true)->orderBy('company_name')->orderBy('name')->get(); }
    private function validated(Request $request): array
    {
        return $request->validate(['customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $this->company()->id)], 'name' => 'required|string|max:191', 'site_location' => 'nullable|string|max:191', 'manager_name' => 'nullable|string|max:191', 'status' => 'required|in:planning,active,on_hold,completed,cancelled', 'start_date' => 'nullable|date', 'target_end_date' => 'nullable|date|after_or_equal:start_date', 'budget' => 'required|numeric|min:0', 'description' => 'nullable|string|max:4000', 'notes' => 'nullable|string|max:4000']);
    }
    private function nextNumber(int $companyId): string { return 'PRJ-'.now()->format('Y').'-'.str_pad((string) (Project::where('company_id', $companyId)->count() + 1), 4, '0', STR_PAD_LEFT); }
    private function authorizeProject(Project $project): void { abort_unless($project->company_id === $this->company()->id, 404); }
}
