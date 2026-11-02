<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Customer;
use App\Models\Product;
use App\Http\Controllers\Concerns\ResolvesCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    use ResolvesCompany;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $company = $this->company();
        return view('quotations.index', compact('company'), ['quotations' => Quotation::where('company_id', $company->id)->with('customer')->latest('quotation_date')->paginate(12)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('quotations.form', $this->formData(new Quotation(['quotation_date' => now(), 'valid_until' => now()->addDays(30), 'status' => 'draft'])));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $quotation = DB::transaction(function () use ($request) {
            $company = $this->company();
            $quotation = new Quotation($this->validated($request));
            $quotation->company_id = $company->id;
            $quotation->quotation_number = $this->nextNumber($company->id);
            $quotation->save();
            $this->saveItems($quotation, $request->input('items', []));
            return $quotation;
        });
        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Quotation $quotation)
    {
        $this->authorizeQuotation($quotation);
        return view('quotations.show', ['quotation' => $quotation->load('company', 'customer', 'items.product'), 'company' => $this->company()]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quotation $quotation)
    {
        $this->authorizeQuotation($quotation);
        return view('quotations.form', $this->formData($quotation->load('items')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quotation $quotation)
    {
        $this->authorizeQuotation($quotation);
        DB::transaction(function () use ($request, $quotation) {
            $quotation->update($this->validated($request));
            $quotation->items()->delete();
            $this->saveItems($quotation, $request->input('items', []));
        });
        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $this->authorizeQuotation($quotation);
        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', 'Quotation removed.');
    }

    private function formData(Quotation $quotation): array
    {
        $company = $this->company();
        return ['quotation' => $quotation, 'company' => $company, 'customers' => Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(), 'products' => Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get()];
    }
    private function validated(Request $request): array
    {
        return $request->validate(['customer_id' => 'required|integer', 'quotation_date' => 'required|date', 'valid_until' => 'nullable|date|after_or_equal:quotation_date', 'status' => 'required|in:draft,sent,accepted,rejected,expired,converted', 'notes' => 'nullable|string', 'terms' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:191', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit' => 'required|string|max:20', 'items.*.unit_price' => 'required|numeric|min:0', 'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100', 'items.*.vat_rate' => 'nullable|numeric|min:0|max:100', 'items.*.product_id' => 'nullable|integer']);
    }
    private function saveItems(Quotation $quotation, array $items): void
    {
        $subtotal = $discountTotal = $vatTotal = 0;
        foreach ($items as $order => $item) {
            $quantity = (float) $item['quantity']; $price = (float) $item['unit_price']; $discount = (float) ($item['discount_percentage'] ?? 0); $vatRate = (float) ($item['vat_rate'] ?? 5);
            $gross = $quantity * $price; $discountAmount = $gross * ($discount / 100); $lineSubtotal = $gross - $discountAmount; $lineVat = $lineSubtotal * ($vatRate / 100);
            $quotation->items()->create(['product_id' => $item['product_id'] ?: null, 'sort_order' => $order, 'description' => $item['description'], 'quantity' => $quantity, 'unit' => $item['unit'], 'unit_price' => $price, 'discount_percentage' => $discount, 'vat_rate' => $vatRate, 'line_subtotal' => $lineSubtotal, 'line_vat' => $lineVat, 'line_total' => $lineSubtotal + $lineVat]);
            $subtotal += $gross; $discountTotal += $discountAmount; $vatTotal += $lineVat;
        }
        $quotation->update(['subtotal' => $subtotal, 'discount_total' => $discountTotal, 'vat_total' => $vatTotal, 'total' => $subtotal - $discountTotal + $vatTotal]);
    }
    private function nextNumber(int $companyId): string { return 'QT-'.now()->format('Y').'-'.str_pad((string) (Quotation::where('company_id', $companyId)->count() + 1), 4, '0', STR_PAD_LEFT); }
    private function authorizeQuotation(Quotation $quotation): void { abort_unless($quotation->company_id === $this->company()->id, 404); }
}
