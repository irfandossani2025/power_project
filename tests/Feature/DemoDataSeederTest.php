<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Models\Quotation;
use App\Models\StockMovement;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_complete_fictional_demo_book_once(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('suppliers', 2);
        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseCount('quotations', 3);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('purchase_bills', 1);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertGreaterThan(0, StockMovement::count());
        $this->assertGreaterThan(0, Invoice::firstOrFail()->payments()->count());
        $this->assertGreaterThan(0, PurchaseBill::firstOrFail()->payments()->count());
        $this->assertSame('partially_paid', Invoice::firstOrFail()->status);
        $this->assertSame('partially_paid', PurchaseBill::firstOrFail()->status);
        $this->assertSame(1, Expense::count());
        $this->assertSame(1, Quotation::where('status', 'sent')->count());
    }

    public function test_it_refuses_to_mix_demo_data_into_existing_books(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->expectException(RuntimeException::class);
        $this->seed(DemoDataSeeder::class);
    }
}
