<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->enum('provider', ['openai', 'anthropic'])->default('openai');
            $table->string('model', 100)->nullable();
            $table->text('api_key')->nullable();
            $table->text('company_guidance')->nullable();
            $table->boolean('allow_draft_actions')->default(true);
            $table->unsignedInteger('monthly_request_limit')->default(500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_settings');
    }
};
