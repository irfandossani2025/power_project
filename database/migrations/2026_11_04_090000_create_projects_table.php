<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('project_number', 50);
            $table->string('name', 191);
            $table->string('site_location', 191)->nullable();
            $table->string('manager_name', 191)->nullable();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->decimal('budget', 14, 3)->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'project_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('projects'); }
};
