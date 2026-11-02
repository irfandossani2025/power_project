<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_open_the_new_quotation_screen(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/quotations/create');

        $response->assertOk();
        $response->assertSee('New quotation');
    }
}
