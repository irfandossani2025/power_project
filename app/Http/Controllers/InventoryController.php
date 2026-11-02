<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        $products = Product::where('company_id', $company->id)->where('type', 'product')->withSum('stockMovements as stock_on_hand', 'quantity')->orderBy('name')->paginate(20);
        $warehouses = Warehouse::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $movements = StockMovement::where('company_id', $company->id)->with(['product', 'warehouse'])->latest('movement_date')->latest('id')->take(8)->get();
        return view('inventory.index', compact('company', 'products', 'warehouses', 'movements'));
    }

    public function store(Request $request)
    {
        $company = $this->company();
        $data = $request->validate(['product_id' => 'required|integer', 'warehouse_id' => 'nullable|integer', 'type' => 'required|in:opening,receipt,adjustment,return', 'quantity' => 'required|numeric|not_in:0', 'unit_cost' => 'nullable|numeric|min:0', 'movement_date' => 'required|date', 'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000']);
        $product = Product::where('company_id', $company->id)->where('type', 'product')->findOrFail($data['product_id']);
        if (!empty($data['warehouse_id'])) Warehouse::where('company_id', $company->id)->findOrFail($data['warehouse_id']);
        StockMovement::create($data + ['company_id' => $company->id, 'unit_cost' => $data['unit_cost'] ?? $product->cost_price, 'created_by' => $request->user()->id]);
        return redirect()->route('inventory.index')->with('success', 'Stock movement saved. Available quantity is updated immediately.');
    }
}
