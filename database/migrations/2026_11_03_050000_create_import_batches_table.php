<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['customers', 'items']);
            $table->string('original_filename');
            $table->enum('status', ['completed', 'completed_with_errors', 'failed']);
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'type', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('import_batches'); }
};
