<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_users_cannot_access_inventory_or_financial_reports(): void
    {
        Company::create(['name' => 'My Business']);
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->get(route('quotations.index'))->assertOk();
        $this->actingAs($sales)->get(route('inventory.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('reports.index'))->assertForbidden();
    }

    public function test_inventory_users_cannot_access_sales_or_financial_reports(): void
    {
        Company::create(['name' => 'My Business']);
        $inventory = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($inventory)->get(route('inventory.index'))->assertOk();
        $this->actingAs($inventory)->get(route('quotations.index'))->assertForbidden();
        $this->actingAs($inventory)->get(route('reports.index'))->assertForbidden();
    }

    public function test_finance_buddy_accepts_floating_chat_json_requests(): void
    {
        Company::create(['name' => 'My Business']);
        $user = User::factory()->create(['role' => 'sales']);

        $this->actingAs($user)
            ->postJson(route('finance-buddy.ask'), ['prompt' => 'Which stock is low?'])
            ->assertOk()
            ->assertJsonStructure(['answer', 'provider', 'actions', 'status']);
    }
}
