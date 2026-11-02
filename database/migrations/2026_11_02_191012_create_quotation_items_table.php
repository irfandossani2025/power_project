<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 20)->default('Unit');
            $table->decimal('unit_price', 14, 3)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('line_subtotal', 14, 3)->default(0);
            $table->decimal('line_vat', 14, 3)->default(0);
            $table->decimal('line_total', 14, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
