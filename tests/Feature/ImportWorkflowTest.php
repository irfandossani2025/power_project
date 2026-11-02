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
}
