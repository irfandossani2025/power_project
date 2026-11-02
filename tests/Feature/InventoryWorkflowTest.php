<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_movements_update_the_inventory_view(): void
    {
        $company = Company::create(['name' => 'My Business']);
        $product = Product::create(['company_id' => $company->id, 'name' => 'Cable', 'type' => 'product', 'unit' => 'Roll', 'cost_price' => 2, 'selling_price' => 3, 'vat_rate' => 5]);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('inventory.movements.store'), ['product_id' => $product->id, 'type' => 'opening', 'quantity' => 12, 'movement_date' => '2026-11-03'])->assertRedirect(route('inventory.index'));
        $this->actingAs($user)->get(route('inventory.index'))->assertOk()->assertSee('12.000')->assertSee('Cable');
    }
}
