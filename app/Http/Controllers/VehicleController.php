<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        $vehicles = Vehicle::where('company_id', $company->id)->orderByDesc('is_active')->orderBy('vehicle_number')->get();

        return view('vehicles.index', compact('company', 'vehicles'));
    }

    public function create()
    {
        return view('vehicles.form', ['company' => $this->company(), 'vehicle' => new Vehicle]);
    }

    public function store(Request $request)
    {
        Vehicle::create($this->validated($request) + ['company_id' => $this->company()->id]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle saved. Registration and insurance dates will appear in compliance alerts.');
    }

    public function edit(Vehicle $vehicle)
    {
        $this->ensureCompany($vehicle);

        return view('vehicles.form', ['company' => $this->company(), 'vehicle' => $vehicle]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->ensureCompany($vehicle);
        $vehicle->update($this->validated($request, $vehicle));

        return redirect()->route('vehicles.index')->with('success', 'Vehicle record updated.');
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate(['vehicle_number' => ['required', 'string', 'max:80', Rule::unique('vehicles', 'vehicle_number')->where('company_id', $this->company()->id)->ignore($vehicle?->id)], 'registration_plate' => 'required|string|max:80', 'make' => 'nullable|string|max:100', 'model' => 'nullable|string|max:100', 'model_year' => 'nullable|integer|min:1900|max:2100', 'colour' => 'nullable|string|max:50', 'assigned_to' => 'nullable|string|max:120', 'mulkiya_number' => 'nullable|string|max:80', 'mulkiya_expiry_date' => 'nullable|date', 'insurance_provider' => 'nullable|string|max:120', 'insurance_policy_number' => 'nullable|string|max:100', 'insurance_expiry_date' => 'nullable|date', 'inspection_expiry_date' => 'nullable|date', 'is_active' => 'nullable|boolean', 'notes' => 'nullable|string|max:4000']) + ['is_active' => $request->boolean('is_active')];
    }

    private function ensureCompany(Vehicle $vehicle): void
    {
        abort_unless($vehicle->company_id === $this->company()->id, 404);
    }
}
