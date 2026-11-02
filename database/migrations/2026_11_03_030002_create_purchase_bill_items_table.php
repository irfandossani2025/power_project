<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 20)->default('Unit');
            $table->decimal('unit_cost', 14, 3)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('line_subtotal', 14, 3)->default(0);
            $table->decimal('line_vat', 14, 3)->default(0);
            $table->decimal('line_total', 14, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_bill_items'); }
};
