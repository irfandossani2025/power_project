<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('bill_number');
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'received', 'partially_paid', 'paid', 'void'])->default('received');
            $table->string('currency', 3)->default('OMR');
            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('vat_total', 14, 3)->default(0);
            $table->decimal('total', 14, 3)->default(0);
            $table->decimal('paid_total', 14, 3)->default(0);
            $table->decimal('balance_due', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'bill_number']);
            $table->index(['company_id', 'status', 'due_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_bills'); }
};
