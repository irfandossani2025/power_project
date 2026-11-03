<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\Quotation;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Seeds a clearly fictional demonstration book. It deliberately refuses to mix with live records. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Invoice::exists() || PurchaseBill::exists() || Expense::exists() || Quotation::exists() || StockMovement::exists()) {
            throw new RuntimeException('Demo data was not added because transactional records already exist.');
        }

        DB::transaction(function (): void {
            $company = Company::firstOrCreate(['name' => 'My Business'], ['legal_name' => 'My Business', 'currency' => 'OMR']);
            $user = User::first();
            $today = now()->startOfDay();
            $warehouse = Warehouse::firstOrCreate(['company_id' => $company->id, 'code' => 'MCT-01'], ['name' => 'Muscat Main Store', 'address' => 'Muscat, Sultanate of Oman', 'is_active' => true]);

            $customers = collect([
                ['name' => 'Aisha Al Balushi', 'company_name' => 'Al Noor Interiors LLC', 'email' => 'aisha@alnoor-demo.om', 'phone' => '+968 9211 0401'],
                ['name' => 'Khalid Al Habsi', 'company_name' => 'Nizwa Facilities', 'email' => 'khalid@nizwa-demo.om', 'phone' => '+968 9211 0402'],
                ['name' => 'Mariam Al Lawati', 'company_name' => 'Seeb Hospitality', 'email' => 'mariam@seeb-demo.om', 'phone' => '+968 9211 0403'],
            ])->map(fn (array $data, int $index) => Customer::firstOrCreate(['company_id' => $company->id, 'email' => $data['email']], $data + ['company_id' => $company->id, 'vat_number' => 'OM-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'billing_address' => 'Muscat, Sultanate of Oman', 'payment_terms_days' => 30, 'credit_limit' => 2000, 'is_active' => true]));

            $suppliers = collect([
                ['name' => 'Saeed Al Riyami', 'company_name' => 'Oman Electrical Supplies', 'email' => 'sales@oman-electrical-demo.om', 'phone' => '+968 9411 0501'],
                ['name' => 'Fatma Al Hinai', 'company_name' => 'Gulf Office Solutions', 'email' => 'orders@gulf-office-demo.om', 'phone' => '+968 9411 0502'],
            ])->map(fn (array $data, int $index) => Supplier::firstOrCreate(['company_id' => $company->id, 'email' => $data['email']], $data + ['company_id' => $company->id, 'vat_number' => 'OM-SUP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'billing_address' => 'Muscat, Sultanate of Oman', 'payment_terms_days' => 30, 'is_active' => true]));

            $products = collect([
                ['sku' => 'LED-18W', 'name' => 'LED Panel Light 18W', 'description' => 'Recessed cool-white LED panel', 'type' => 'product', 'unit' => 'pcs', 'cost_price' => 15, 'selling_price' => 25, 'reorder_level' => 12],
                ['sku' => 'CBL-2.5', 'name' => 'Copper Cable 2.5mm', 'description' => 'Electrical cable coil', 'type' => 'product', 'unit' => 'roll', 'cost_price' => 18, 'selling_price' => 30, 'reorder_level' => 8],
                ['sku' => 'SW-1G', 'name' => 'Switch 1 Gang', 'description' => 'White wall switch', 'type' => 'product', 'unit' => 'pcs', 'cost_price' => 2.2, 'selling_price' => 4.5, 'reorder_level' => 20],
                ['sku' => 'INSTALL', 'name' => 'Electrical installation', 'description' => 'Professional installation service', 'type' => 'service', 'unit' => 'job', 'cost_price' => 0, 'selling_price' => 85, 'reorder_level' => 0],
            ])->map(fn (array $data) => Product::firstOrCreate(['company_id' => $company->id, 'sku' => $data['sku']], $data + ['company_id' => $company->id, 'vat_rate' => 5, 'is_active' => true]));

            foreach ([[$products[0], 8], [$products[1], 5], [$products[2], 42]] as [$product, $quantity]) {
                StockMovement::create(['company_id' => $company->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'type' => 'opening', 'quantity' => $quantity, 'unit_cost' => $product->cost_price, 'movement_date' => $today->copy()->subDays(14), 'reference' => 'DEMO-OPEN', 'notes' => 'Demo opening stock', 'created_by' => $user?->id]);
            }

            $this->quotation($company->id, $customers[0]->id, $products[1], 'QT-DEMO-1001', 'sent', $today->copy()->subDays(2), 3);
            $this->quotation($company->id, $customers[1]->id, $products[0], 'QT-DEMO-1002', 'draft', $today, 12);
            $converted = $this->quotation($company->id, $customers[2]->id, $products[0], 'QT-DEMO-1003', 'converted', $today->copy()->subDays(8), 10);

            $bill = PurchaseBill::create(['company_id' => $company->id, 'supplier_id' => $suppliers[0]->id, 'bill_number' => 'BILL-DEMO-2031', 'bill_date' => $today->copy()->subDays(6), 'due_date' => $today->copy()->addDays(24), 'status' => 'partially_paid', 'currency' => 'OMR', 'subtotal' => 900, 'vat_total' => 45, 'total' => 945, 'paid_total' => 300, 'balance_due' => 645, 'notes' => 'Demo stock purchase']);
            foreach ([[$products[0], 40, 15], [$products[1], 15, 18]] as $index => [$product, $quantity, $cost]) {
                $subtotal = $quantity * $cost; $vat = round($subtotal * .05, 3);
                $bill->items()->create(['product_id' => $product->id, 'sort_order' => $index, 'description' => $product->name, 'quantity' => $quantity, 'unit' => $product->unit, 'unit_cost' => $cost, 'vat_rate' => 5, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $subtotal + $vat]);
                StockMovement::create(['company_id' => $company->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'type' => 'receipt', 'quantity' => $quantity, 'unit_cost' => $cost, 'movement_date' => $bill->bill_date, 'reference' => $bill->bill_number, 'notes' => 'Demo supplier receipt', 'created_by' => $user?->id]);
            }
            $posting = app(AccountingPostingService::class);
            $posting->postPurchaseBill($bill);
            $supplierPayment = $bill->payments()->create(['company_id' => $company->id, 'payment_date' => $today->copy()->subDays(3), 'amount' => 300, 'method' => 'bank_transfer', 'reference' => 'DEMO-PO-300', 'notes' => 'Demo supplier payment']);
            $posting->postSupplierPayment($supplierPayment);

            $invoice = Invoice::create(['company_id' => $company->id, 'customer_id' => $customers[2]->id, 'quotation_id' => $converted->id, 'invoice_number' => 'INV-DEMO-3001', 'invoice_date' => $today->copy()->subDays(4), 'due_date' => $today->copy()->addDays(26), 'status' => 'partially_paid', 'currency' => 'OMR', 'subtotal' => 335, 'discount_total' => 0, 'vat_total' => 16.75, 'total' => 351.75, 'paid_total' => 150, 'balance_due' => 201.75, 'notes' => 'Demo invoice generated from accepted quotation', 'terms' => 'Payment due within 30 days.']);
            foreach ([[$products[0], 10, 25], [$products[3], 1, 85]] as $index => [$product, $quantity, $price]) {
                $subtotal = $quantity * $price; $vat = round($subtotal * .05, 3);
                $invoice->items()->create(['product_id' => $product->id, 'sort_order' => $index, 'description' => $product->name, 'quantity' => $quantity, 'unit' => $product->unit, 'unit_price' => $price, 'discount_percentage' => 0, 'vat_rate' => 5, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $subtotal + $vat]);
                if ($product->type === 'product') StockMovement::create(['company_id' => $company->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'type' => 'sale', 'quantity' => -$quantity, 'unit_cost' => $product->cost_price, 'movement_date' => $invoice->invoice_date, 'reference' => $invoice->invoice_number, 'notes' => 'Demo invoice issue', 'created_by' => $user?->id]);
            }
            $posting->postInvoice($invoice);
            $payment = $invoice->payments()->create(['company_id' => $company->id, 'payment_date' => $today->copy()->subDays(1), 'amount' => 150, 'method' => 'bank_transfer', 'reference' => 'DEMO-RCPT-150', 'notes' => 'Demo customer payment']);
            $posting->postCustomerPayment($payment);

            $expense = Expense::create(['company_id' => $company->id, 'expense_date' => $today->copy()->subDays(1), 'category' => 'Transport & fuel', 'payee' => 'Muscat Fuel Station', 'reference' => 'DEMO-EXP-01', 'description' => 'Demo delivery and site-visit fuel expense', 'payment_method' => 'card', 'amount_before_vat' => 45, 'vat_rate' => 5, 'vat_amount' => 2.25, 'total' => 47.25, 'notes' => 'Demo operating expense', 'created_by' => $user?->id]);
            $posting->postExpense($expense);
        });
    }

    private function quotation(int $companyId, int $customerId, Product $product, string $number, string $status, $date, float $quantity): Quotation
    {
        $subtotal = $quantity * (float) $product->selling_price; $vat = round($subtotal * .05, 3);
        $quotation = Quotation::create(['company_id' => $companyId, 'customer_id' => $customerId, 'quotation_number' => $number, 'quotation_date' => $date, 'valid_until' => $date->copy()->addDays(30), 'status' => $status, 'currency' => 'OMR', 'subtotal' => $subtotal, 'discount_total' => 0, 'vat_total' => $vat, 'total' => $subtotal + $vat, 'notes' => 'Demo quotation', 'terms' => 'Prices exclude delivery unless stated.']);
        $quotation->items()->create(['product_id' => $product->id, 'sort_order' => 0, 'description' => $product->name, 'quantity' => $quantity, 'unit' => $product->unit, 'unit_price' => $product->selling_price, 'discount_percentage' => 0, 'vat_rate' => 5, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $subtotal + $vat]);
        return $quotation;
    }
}
