<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->after('customer_id')->constrained()->nullOnDelete());
        Schema::table('purchase_bills', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete());
        Schema::table('expenses', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->after('company_id')->constrained()->nullOnDelete());
        Schema::table('invoices', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->after('quotation_id')->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::table('expenses', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::table('purchase_bills', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::table('quotations', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
    }
};
