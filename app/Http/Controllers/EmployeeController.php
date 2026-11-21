<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        $employees = Employee::where('company_id', $company->id)->orderByDesc('is_active')->orderBy('full_name')->get();

        return view('employees.index', compact('company', 'employees'));
    }

    public function create()
    {
        return view('employees.form', ['company' => $this->company(), 'employee' => new Employee]);
    }

    public function store(Request $request)
    {
        Employee::create($this->validated($request) + ['company_id' => $this->company()->id]);

        return redirect()->route('employees.index')->with('success', 'Employee saved. Expiry dates will appear in compliance alerts.');
    }

    public function edit(Employee $employee)
    {
        $this->ensureCompany($employee);

        return view('employees.form', ['company' => $this->company(), 'employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->ensureCompany($employee);
        $employee->update($this->validated($request, $employee));

        return redirect()->route('employees.index')->with('success', 'Employee record updated.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate(['employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->where('company_id', $this->company()->id)->ignore($employee?->id)], 'full_name' => 'required|string|max:255', 'nationality' => 'nullable|string|max:80', 'is_omani' => 'nullable|boolean', 'department' => 'nullable|string|max:100', 'job_title' => 'nullable|string|max:120', 'phone' => 'nullable|string|max:32', 'email' => 'nullable|email|max:255', 'joining_date' => 'nullable|date', 'visa_number' => 'nullable|string|max:80', 'visa_expiry_date' => 'nullable|date', 'resident_card_number' => 'nullable|string|max:80', 'resident_card_expiry_date' => 'nullable|date', 'passport_number' => 'nullable|string|max:80', 'passport_expiry_date' => 'nullable|date', 'omani_id_number' => 'nullable|string|max:80', 'omani_id_expiry_date' => 'nullable|date', 'is_active' => 'nullable|boolean', 'notes' => 'nullable|string|max:4000']) + ['is_omani' => $request->boolean('is_omani'), 'is_active' => $request->boolean('is_active')];
    }

    private function ensureCompany(Employee $employee): void
    {
        abort_unless($employee->company_id === $this->company()->id, 404);
    }
}
