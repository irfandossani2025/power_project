<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number', 40);
            $table->date('entry_date');
            $table->string('reference', 100)->nullable();
            $table->text('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'entry_number']);
            $table->index(['company_id', 'entry_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('journal_entries'); }
};
