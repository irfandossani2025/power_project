<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'void'])->default('draft');
            $table->string('currency', 3)->default('OMR');
            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('discount_total', 14, 3)->default(0);
            $table->decimal('vat_total', 14, 3)->default(0);
            $table->decimal('total', 14, 3)->default(0);
            $table->decimal('paid_total', 14, 3)->default(0);
            $table->decimal('balance_due', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'due_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('invoices'); }
};
