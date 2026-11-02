<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;
    public function test_an_authenticated_user_can_open_business_reports(): void
    {
        Company::create(['name' => 'My Business']);
        $this->actingAs(User::factory()->create())->get(route('reports.index'))->assertOk()->assertSee('Business reports')->assertSee('Oman VAT summary');
    }
}
