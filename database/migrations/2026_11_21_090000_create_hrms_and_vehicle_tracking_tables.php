<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 50);
            $table->string('full_name');
            $table->string('nationality', 80)->nullable();
            $table->boolean('is_omani')->default(false);
            $table->string('department', 100)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('visa_number', 80)->nullable();
            $table->date('visa_expiry_date')->nullable();
            $table->string('resident_card_number', 80)->nullable();
            $table->date('resident_card_expiry_date')->nullable();
            $table->string('passport_number', 80)->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('omani_id_number', 80)->nullable();
            $table->date('omani_id_expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'employee_number']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle_number', 80);
            $table->string('registration_plate', 80);
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('model_year')->nullable();
            $table->string('colour', 50)->nullable();
            $table->string('assigned_to', 120)->nullable();
            $table->string('mulkiya_number', 80)->nullable();
            $table->date('mulkiya_expiry_date')->nullable();
            $table->string('insurance_provider', 120)->nullable();
            $table->string('insurance_policy_number', 100)->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('inspection_expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'vehicle_number']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('employees');
    }
};
