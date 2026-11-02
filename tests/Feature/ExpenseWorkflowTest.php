<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use RefreshDatabase;
    public function test_an_expense_calculates_oman_vat(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $this->actingAs($user)->post(route('expenses.store'), ['expense_date'=>'2026-11-03','category'=>'Transport & fuel','description'=>'Fuel','payment_method'=>'cash','amount_before_vat'=>10,'vat_rate'=>5])->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses',['description'=>'Fuel','vat_amount'=>0.5,'total'=>10.5]);
    }
}
