<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportWorkflowTest extends TestCase
{
    use RefreshDatabase;
    public function test_customer_csv_import_creates_customers_and_audit_batch(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $file=UploadedFile::fake()->createWithContent('customers.csv',"name,company_name,email,phone,vat_number,billing_address,payment_terms_days\nAli,Ali Trading,ali@example.com,+96890000000,OM1,Muscat,30\n");
        $this->actingAs($user)->post(route('imports.store'),['type'=>'customers','file'=>$file])->assertRedirect(route('imports.index'));
        $this->assertDatabaseHas('customers',['name'=>'Ali','email'=>'ali@example.com']); $this->assertDatabaseHas('import_batches',['type'=>'customers','imported_count'=>1]);
    }

    public function test_supplier_csv_import_creates_suppliers_and_audit_batch(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $file=UploadedFile::fake()->createWithContent('suppliers.csv',"name,company_name,email,phone,vat_number,billing_address,payment_terms_days\nSalim,Salim Supplies,salim@example.com,+96890000000,OM2,Muscat,45\n");
        $this->actingAs($user)->post(route('imports.store'),['type'=>'suppliers','file'=>$file])->assertRedirect(route('imports.index'));
        $this->assertDatabaseHas('suppliers',['name'=>'Salim','email'=>'salim@example.com']); $this->assertDatabaseHas('import_batches',['type'=>'suppliers','imported_count'=>1]);
    }

    public function test_opening_balance_csv_creates_a_balanced_journal_and_audit_batch(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $file=UploadedFile::fake()->createWithContent('opening.csv',"entry_date,account_code,account_name,account_type,debit,credit\n2026-01-01,1000,Cash and bank,asset,15000,0\n2026-01-01,3000,Owner equity,equity,0,15000\n");
        $this->actingAs($user)->post(route('imports.store'),['type'=>'opening_balances','file'=>$file])->assertRedirect(route('imports.index'));
        $this->assertDatabaseHas('import_batches',['type'=>'opening_balances','imported_count'=>2]);
        $this->assertDatabaseHas('journal_entries',['source_type'=>'opening_balance_import','entry_number'=>'QB-OPEN-1']);
        $this->assertDatabaseHas('journal_lines',['debit'=>15000]); $this->assertDatabaseHas('journal_lines',['credit'=>15000]);
    }

    public function test_opening_balance_import_cannot_be_run_twice(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $contents="entry_date,account_code,account_name,account_type,debit,credit\n2026-01-01,1000,Cash and bank,asset,15000,0\n2026-01-01,3000,Owner equity,equity,0,15000\n";
        $this->actingAs($user)->post(route('imports.store'),['type'=>'opening_balances','file'=>UploadedFile::fake()->createWithContent('opening.csv',$contents)]);
        $this->actingAs($user)->post(route('imports.store'),['type'=>'opening_balances','file'=>UploadedFile::fake()->createWithContent('opening-again.csv',$contents)])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('journal_entries',1);
    }

    public function test_historical_invoice_csv_creates_invoice_payment_and_ledger_entries(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $csv="invoice_number,invoice_date,due_date,customer_email,customer_name,line_description,quantity,unit,unit_price,discount_percentage,vat_rate,payment_amount,payment_date,payment_method,payment_reference\nQB-INV-1001,2025-12-15,2026-01-14,accounts@example.com,Al Noor Trading,Consulting,1,service,100,0,5,105,2025-12-15,bank_transfer,QB-RCPT-1\nQB-INV-1001,2025-12-15,2026-01-14,accounts@example.com,Al Noor Trading,Support,1,hour,50,0,5,,,,\n";
        $this->actingAs($user)->post(route('imports.store'),['type'=>'historical_invoices','file'=>UploadedFile::fake()->createWithContent('historical-invoices.csv',$csv)])->assertRedirect(route('imports.index'));
        $this->assertDatabaseHas('invoices',['invoice_number'=>'QB-INV-1001','total'=>157.5,'paid_total'=>105,'balance_due'=>52.5,'status'=>'partially_paid']);
        $this->assertDatabaseCount('invoice_items',2); $this->assertDatabaseCount('payments',1);
        $this->assertDatabaseHas('journal_entries',['source_type'=>'invoice']); $this->assertDatabaseHas('journal_entries',['source_type'=>'customer_payment']);
        $this->assertDatabaseHas('import_batches',['type'=>'historical_invoices','imported_count'=>1]);
    }

    public function test_historical_supplier_bill_csv_creates_bill_payment_and_ledger_entries(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $csv="bill_number,bill_date,due_date,supplier_email,supplier_name,line_description,quantity,unit,unit_cost,vat_rate,payment_amount,payment_date,payment_method,payment_reference\nQB-BILL-1001,2025-12-15,2026-01-14,purchases@example.com,Oman Supplies,Office supplies,1,unit,100,5,105,2025-12-15,bank_transfer,QB-PAY-1\nQB-BILL-1001,2025-12-15,2026-01-14,purchases@example.com,Oman Supplies,Delivery,1,job,50,5,,,,\n";
        $this->actingAs($user)->post(route('imports.store'),['type'=>'historical_bills','file'=>UploadedFile::fake()->createWithContent('historical-bills.csv',$csv)])->assertRedirect(route('imports.index'));
        $this->assertDatabaseHas('purchase_bills',['bill_number'=>'QB-BILL-1001','total'=>157.5,'paid_total'=>105,'balance_due'=>52.5,'status'=>'partially_paid']);
        $this->assertDatabaseCount('purchase_bill_items',2); $this->assertDatabaseCount('supplier_payments',1);
        $this->assertDatabaseHas('journal_entries',['source_type'=>'purchase_bill']); $this->assertDatabaseHas('journal_entries',['source_type'=>'supplier_payment']);
        $this->assertDatabaseHas('import_batches',['type'=>'historical_bills','imported_count'=>1]);
    }

    public function test_historical_invoice_accepts_a_csv_with_only_the_required_columns(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $csv="invoice_number,invoice_date,customer_name,line_description,quantity,unit,unit_price,discount_percentage,vat_rate\nQB-INV-MIN,2025-12-15,Al Noor Trading,Consulting,1,service,100,0,5\n";

        $this->actingAs($user)->post(route('imports.store'),['type'=>'historical_invoices','file'=>UploadedFile::fake()->createWithContent('historical-invoices-minimum.csv',$csv)])->assertRedirect(route('imports.index'));

        $this->assertDatabaseHas('invoices',['invoice_number'=>'QB-INV-MIN','total'=>105,'paid_total'=>0,'balance_due'=>105,'status'=>'issued']);
        $this->assertDatabaseHas('import_batches',['type'=>'historical_invoices','imported_count'=>1,'skipped_count'=>0]);
    }

    public function test_invalid_historical_invoice_is_fully_rolled_back_and_audited(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $csv="invoice_number,invoice_date,due_date,customer_email,customer_name,line_description,quantity,unit,unit_price,discount_percentage,vat_rate,payment_amount,payment_date,payment_method,payment_reference\nQB-BAD-1,2025-12-15,2026-01-14,bad@example.com,Bad Import,Consulting,1,service,100,0,5,200,2025-12-15,bank_transfer,QB-RCPT-BAD\n";
        $this->actingAs($user)->post(route('imports.store'),['type'=>'historical_invoices','file'=>UploadedFile::fake()->createWithContent('bad-invoice.csv',$csv)])->assertRedirect(route('imports.index'));
        $this->assertDatabaseMissing('invoices',['invoice_number'=>'QB-BAD-1']);
        $this->assertDatabaseMissing('customers',['email'=>'bad@example.com']);
        $this->assertDatabaseHas('import_batches',['type'=>'historical_invoices','imported_count'=>0,'skipped_count'=>1]);
    }
}
