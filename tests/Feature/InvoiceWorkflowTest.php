<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_quotation_can_be_converted_to_an_invoice_and_paid(): void
    {
        $company = Company::create(['name' => 'My Business']);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Test Customer']);
        $quotation = Quotation::create(['company_id'=>$company->id,'customer_id'=>$customer->id,'quotation_number'=>'QT-2026-0001','quotation_date'=>now(),'status'=>'accepted','subtotal'=>100,'vat_total'=>5,'total'=>105]);
        $quotation->items()->create(['description'=>'Installation service','quantity'=>1,'unit'=>'Service','unit_price'=>100,'vat_rate'=>5,'line_subtotal'=>100,'line_vat'=>5,'line_total'=>105]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('quotations.invoice', $quotation))->assertRedirect();
        $invoice = \App\Models\Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame('105.000', $invoice->balance_due);
        $this->assertDatabaseHas('journal_entries', ['company_id'=>$company->id, 'source_type'=>'invoice', 'source_id'=>$invoice->id]);
        $this->assertDatabaseHas('journal_lines', ['account_id'=>\App\Models\Account::where('company_id',$company->id)->where('code','1100')->value('id'), 'debit'=>105]);
        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), ['payment_date'=>now()->format('Y-m-d'),'amount'=>105,'method'=>'bank_transfer'])->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('journal_entries', ['company_id'=>$company->id, 'source_type'=>'customer_payment']);
    }
}
