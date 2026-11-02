<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        return view('customers.index', compact('company'), ['customers' => Customer::where('company_id', $company->id)->latest()->paginate(12)]);
    }

    public function create() { return view('customers.form', ['customer' => new Customer, 'company' => $this->company()]); }

    public function store(Request $request)
    {
        $customer = new Customer($this->validated($request));
        $customer->company_id = $this->company()->id;
        $customer->save();
        return redirect()->route('customers.index')->with('success', 'Customer saved successfully.');
    }

    public function edit(Customer $customer) { $this->authorizeCustomer($customer); return view('customers.form', ['customer' => $customer, 'company' => $this->company()]); }
    public function update(Request $request, Customer $customer) { $this->authorizeCustomer($customer); $customer->update($this->validated($request)); return redirect()->route('customers.index')->with('success', 'Customer updated successfully.'); }
    public function destroy(Customer $customer) { $this->authorizeCustomer($customer); $customer->delete(); return redirect()->route('customers.index')->with('success', 'Customer removed.'); }

    private function validated(Request $request): array { return $request->validate(['name' => 'required|string|max:191', 'company_name' => 'nullable|string|max:191', 'email' => 'nullable|email|max:191', 'phone' => 'nullable|string|max:32', 'vat_number' => 'nullable|string|max:32', 'billing_address' => 'nullable|string', 'payment_terms_days' => 'required|integer|min:0|max:365', 'credit_limit' => 'nullable|numeric|min:0', 'is_active' => 'boolean']); }
    private function authorizeCustomer(Customer $customer): void { abort_unless($customer->company_id === $this->company()->id, 404); }
}
