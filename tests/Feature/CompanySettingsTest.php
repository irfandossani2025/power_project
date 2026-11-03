<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_business_identity_without_creating_a_second_company(): void
    {
        $company=Company::create(['name'=>'My Business','currency'=>'OMR']); $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->patch(route('settings.company.update'),['name'=>'Power Projects','legal_name'=>'Power Projects LLC','vat_number'=>'OM1234567890','currency'=>'OMR','phone'=>'+96890000000','email'=>'accounts@example.com','address'=>'Muscat, Oman'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies',['id'=>$company->id,'name'=>'Power Projects','vat_number'=>'OM1234567890']);
        $this->assertDatabaseCount('companies',1);
    }
}
