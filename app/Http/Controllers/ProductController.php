<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        return view('products.index', compact('company'), ['products' => Product::where('company_id', $company->id)->latest()->paginate(12)]);
    }
    public function create() { return view('products.form', ['product' => new Product(['type' => 'product', 'unit' => 'Unit', 'vat_rate' => 5]), 'company' => $this->company()]); }
    public function store(Request $request) { $product = new Product($this->validated($request)); $product->company_id = $this->company()->id; $product->save(); return redirect()->route('products.index')->with('success', 'Item saved successfully.'); }
    public function edit(Product $product) { $this->authorizeProduct($product); return view('products.form', ['product' => $product, 'company' => $this->company()]); }
    public function update(Request $request, Product $product) { $this->authorizeProduct($product); $product->update($this->validated($request)); return redirect()->route('products.index')->with('success', 'Item updated successfully.'); }
    public function destroy(Product $product) { $this->authorizeProduct($product); $product->delete(); return redirect()->route('products.index')->with('success', 'Item removed.'); }
    private function validated(Request $request): array { return $request->validate(['sku' => 'nullable|string|max:191', 'name' => 'required|string|max:191', 'description' => 'nullable|string', 'type' => 'required|in:product,service', 'unit' => 'required|string|max:20', 'cost_price' => 'required|numeric|min:0', 'selling_price' => 'required|numeric|min:0', 'vat_rate' => 'required|numeric|min:0|max:100', 'reorder_level' => 'required|numeric|min:0', 'is_active' => 'boolean']); }
    private function authorizeProduct(Product $product): void { abort_unless($product->company_id === $this->company()->id, 404); }
}
