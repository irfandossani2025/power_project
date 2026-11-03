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
}
