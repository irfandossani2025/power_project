<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 3)->default(0);
            $table->decimal('credit', 14, 3)->default(0);
            $table->timestamps();
            $table->index(['account_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('journal_lines'); }
};
