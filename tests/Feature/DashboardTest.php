<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_live_sales_and_receivable_metrics(): void
    {
        $company=Company::create(['name'=>'My Business']); $customer=Customer::create(['company_id'=>$company->id,'name'=>'Customer']);
        Invoice::create(['company_id'=>$company->id,'customer_id'=>$customer->id,'invoice_number'=>'INV-DASH-1','invoice_date'=>now(),'status'=>'issued','currency'=>'OMR','subtotal'=>100,'vat_total'=>5,'total'=>105,'paid_total'=>0,'balance_due'=>105]);
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertSee('Sales invoiced')->assertSee('Customers owe')->assertSee('OMR 105.000')->assertSee('Recent sales invoices');
    }
}
