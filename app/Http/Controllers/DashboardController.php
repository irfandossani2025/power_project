<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function __invoke()
    {
        $company = $this->company();
        $quotations = Quotation::where('company_id', $company->id)->with('customer')->latest('quotation_date');
        $monthTotal = (clone $quotations)->whereBetween('quotation_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
        $openTotal = (clone $quotations)->whereIn('status', ['draft', 'sent'])->sum('total');

        return view('dashboard', [
            'company' => $company,
            'monthTotal' => $monthTotal,
            'openTotal' => $openTotal,
            'customers' => Customer::where('company_id', $company->id)->where('is_active', true)->count(),
            'products' => Product::where('company_id', $company->id)->where('is_active', true)->count(),
            'recentQuotations' => $quotations->take(6)->get(),
        ]);
    }
}
