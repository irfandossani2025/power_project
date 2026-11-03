<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_open_the_new_quotation_screen(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/quotations/create');

        $response->assertOk();
        $response->assertSee('New quotation');
    }

    public function test_a_draft_quotation_cannot_be_converted_to_an_invoice(): void
    {
        $company = Company::create(['name' => 'My Business']);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Customer']);
        $quotation = Quotation::create(['company_id' => $company->id, 'customer_id' => $customer->id, 'quotation_number' => 'QT-2026-0001', 'quotation_date' => now(), 'status' => 'draft', 'subtotal' => 100, 'total' => 100]);

        $this->actingAs(User::factory()->create())->post(route('quotations.invoice', $quotation))->assertStatus(422);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame('draft', $quotation->fresh()->status);
    }

    public function test_quotation_rejects_customer_and_item_from_another_business(): void
    {
        $company = Company::create(['name' => 'My Business']);
        $otherCompany = Company::create(['name' => 'Other Business']);
        $foreignCustomer = Customer::create(['company_id' => $otherCompany->id, 'name' => 'Foreign Customer']);
        $foreignProduct = Product::create(['company_id' => $otherCompany->id, 'name' => 'Foreign Item', 'type' => 'product', 'unit' => 'pc']);

        $this->actingAs(User::factory()->create())->from(route('quotations.create'))->post(route('quotations.store'), [
            'customer_id' => $foreignCustomer->id,
            'quotation_date' => '2026-11-03',
            'status' => 'draft',
            'items' => [['product_id' => $foreignProduct->id, 'description' => 'Foreign Item', 'quantity' => 1, 'unit' => 'pc', 'unit_price' => 10]],
        ])->assertRedirect(route('quotations.create'))->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('quotations', 0);
    }
}
