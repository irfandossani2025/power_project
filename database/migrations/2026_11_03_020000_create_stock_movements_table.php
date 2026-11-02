<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['opening', 'receipt', 'adjustment', 'sale', 'return']);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 3)->default(0);
            $table->date('movement_date');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'product_id', 'movement_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
