<?php

namespace Tests\Feature;

use App\Models\AiAssistantSetting;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceBuddyTest extends TestCase
{
    use RefreshDatabase;

    public function test_buddy_uses_guided_mode_and_logs_a_safe_invoice_request(): void
    {
        $company = Company::create(['name' => 'Power Trading', 'currency' => 'OMR']);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Customer']);
        Invoice::create(['company_id' => $company->id, 'customer_id' => $customer->id, 'invoice_number' => 'INV-BUDDY-1', 'invoice_date' => now()->subDays(45), 'due_date' => now()->subDays(10), 'status' => 'issued', 'currency' => 'OMR', 'subtotal' => 100, 'vat_total' => 5, 'total' => 105, 'paid_total' => 0, 'balance_due' => 105]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post(route('finance-buddy.ask'), ['prompt' => 'What invoices are overdue?'])
            ->assertRedirect(route('finance-buddy.index'))
            ->assertSessionHas('buddy_response.answer', fn ($answer) => str_contains($answer, 'overdue invoice'));

        $this->assertDatabaseHas('ai_activity_logs', ['company_id' => $company->id, 'user_id' => $user->id, 'provider' => 'guided', 'status' => 'completed']);
    }

    public function test_admin_can_store_encrypted_buddy_provider_settings(): void
    {
        Company::create(['name' => 'Power Trading', 'currency' => 'OMR']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('settings.finance-buddy.update'), ['enabled' => '1', 'provider' => 'openai', 'model' => 'gpt-4.1-mini', 'api_key' => 'company-secret-key', 'allow_draft_actions' => '1', 'monthly_request_limit' => 600])
            ->assertSessionHasNoErrors();

        $setting = AiAssistantSetting::firstOrFail();
        $this->assertTrue($setting->enabled);
        $this->assertSame('company-secret-key', $setting->api_key);
        $this->assertDatabaseHas('ai_assistant_settings', ['provider' => 'openai', 'monthly_request_limit' => 600]);
    }

    public function test_non_admin_cannot_open_buddy_settings(): void
    {
        Company::create(['name' => 'Power Trading', 'currency' => 'OMR']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('settings.finance-buddy.edit'))->assertForbidden();
    }

    public function test_buddy_translates_vendor_into_supplier_creation_workflow(): void
    {
        Company::create(['name' => 'Power Trading', 'currency' => 'OMR']);
        $inventoryUser = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($inventoryUser)
            ->post(route('finance-buddy.ask'), ['prompt' => 'Please create a new vendor for electrical supplies'])
            ->assertRedirect(route('finance-buddy.index'))
            ->assertSessionHas('buddy_response.actions.0.label', 'Create supplier draft')
            ->assertSessionHas('buddy_response.actions.0.url', route('suppliers.create'));
    }
}
