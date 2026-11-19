<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->nullable();
            $table->string('action', 80)->default('answer');
            $table->text('prompt');
            $table->longText('response')->nullable();
            $table->json('context_snapshot')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->enum('status', ['completed', 'needs_review', 'failed'])->default('completed');
            $table->boolean('approval_required')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_activity_logs');
    }
};
