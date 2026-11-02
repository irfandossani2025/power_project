<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 3);
            $table->enum('method', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('bank_transfer');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'payment_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('payments'); }
};
