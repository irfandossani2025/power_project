<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_bill_receives_product_stock(): void
    {
        $company = Company::create(['name' => 'My Business']);
        $supplier = Supplier::create(['company_id' => $company->id, 'name' => 'Supply Co']);
        $product = Product::create(['company_id' => $company->id, 'name' => 'Socket', 'type' => 'product', 'unit' => 'pc', 'cost_price' => 2, 'selling_price' => 3, 'vat_rate' => 5]);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.store'), ['supplier_id'=>$supplier->id,'bill_number'=>'BILL-001','bill_date'=>'2026-11-03','items'=>[['product_id'=>$product->id,'description'=>'Socket','quantity'=>10,'unit'=>'pc','unit_cost'=>2,'vat_rate'=>5]]])->assertRedirect(route('purchases.index'));
        $this->assertDatabaseHas('purchase_bills', ['bill_number' => 'BILL-001', 'total' => 21]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 10, 'type' => 'receipt']);
    }
}
