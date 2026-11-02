<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ResolvesCompany;

    public function index() { $company = $this->company(); return view('suppliers.index', compact('company'), ['suppliers' => Supplier::where('company_id', $company->id)->latest()->paginate(12)]); }
    public function create() { return view('suppliers.form', ['company' => $this->company(), 'supplier' => new Supplier(['payment_terms_days' => 30])]); }
    public function store(Request $request) { $supplier = new Supplier($this->validated($request)); $supplier->company_id = $this->company()->id; $supplier->save(); return redirect()->route('suppliers.index')->with('success', 'Supplier saved successfully.'); }
    public function edit(Supplier $supplier) { $this->authorizeSupplier($supplier); return view('suppliers.form', ['company' => $this->company(), 'supplier' => $supplier]); }
    public function update(Request $request, Supplier $supplier) { $this->authorizeSupplier($supplier); $supplier->update($this->validated($request)); return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.'); }
    public function destroy(Supplier $supplier) { $this->authorizeSupplier($supplier); $supplier->delete(); return redirect()->route('suppliers.index')->with('success', 'Supplier removed.'); }
    private function validated(Request $request): array { return $request->validate(['name'=>'required|string|max:191','company_name'=>'nullable|string|max:191','email'=>'nullable|email|max:191','phone'=>'nullable|string|max:32','vat_number'=>'nullable|string|max:32','billing_address'=>'nullable|string','payment_terms_days'=>'required|integer|min:0|max:365','is_active'=>'boolean']); }
    private function authorizeSupplier(Supplier $supplier): void { abort_unless($supplier->company_id === $this->company()->id, 404); }
}
