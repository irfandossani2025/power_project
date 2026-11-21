<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\Quotation;
use App\Models\Vehicle;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function __invoke()
    {
        $company = $this->company();
        $quotations = Quotation::where('company_id', $company->id)->with('customer')->latest('quotation_date');
        $monthTotal = (clone $quotations)->whereBetween('quotation_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
        $openTotal = (clone $quotations)->whereIn('status', ['draft', 'sent'])->sum('total');
        $invoices = Invoice::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void']);
        $bills = PurchaseBill::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void']);
        $lowStock = Product::where('company_id', $company->id)->where('type', 'product')->where('reorder_level', '>', 0)->withSum('stockMovements as stock_on_hand', 'quantity')->get()->filter(fn ($product) => (float) ($product->stock_on_hand ?? 0) <= (float) $product->reorder_level);
        $complianceAlerts = collect();
        $addAlerts = function ($records, array $fields, string $group, string $route) use (&$complianceAlerts) {
            foreach ($records as $record) {
                foreach ($fields as $field => $label) {
                    if ($record->{$field} && $record->{$field}->lte(today()->addDays(60))) {
                        $complianceAlerts->push(['group' => $group, 'record' => $record->full_name ?? $record->vehicle_number, 'label' => $label, 'date' => $record->{$field}, 'url' => route($route, $record)]);
                    }
                }
            }
        };
        $addAlerts(Employee::where('company_id', $company->id)->where('is_active', true)->get(), ['visa_expiry_date' => 'Visa', 'resident_card_expiry_date' => 'Resident card', 'passport_expiry_date' => 'Passport', 'omani_id_expiry_date' => 'Omani ID card'], 'Employee', 'employees.edit');
        $addAlerts(Vehicle::where('company_id', $company->id)->where('is_active', true)->get(), ['mulkiya_expiry_date' => 'Mulkiya', 'insurance_expiry_date' => 'Insurance', 'inspection_expiry_date' => 'Inspection'], 'Vehicle', 'vehicles.edit');

        return view('dashboard', [
            'company' => $company,
            'monthTotal' => $monthTotal,
            'openTotal' => $openTotal,
            'salesMonth' => (clone $invoices)->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'receivables' => (clone $invoices)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'),
            'payables' => (clone $bills)->whereIn('status', ['received', 'partially_paid'])->sum('balance_due'),
            'lowStock' => $lowStock,
            'recentInvoices' => $invoices->with('customer')->latest('invoice_date')->take(5)->get(),
            'customers' => Customer::where('company_id', $company->id)->where('is_active', true)->count(),
            'products' => Product::where('company_id', $company->id)->where('is_active', true)->count(),
            'recentQuotations' => $quotations->take(6)->get(),
            'complianceAlerts' => $complianceAlerts->sortBy('date')->values(),
        ]);
    }
}
