<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('category', 100);
            $table->string('payee', 191)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('cash');
            $table->decimal('amount_before_vat', 14, 3)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 14, 3)->default(0);
            $table->decimal('total', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'expense_date', 'category']);
        });
    }
    public function down(): void { Schema::dropIfExists('expenses'); }
};
