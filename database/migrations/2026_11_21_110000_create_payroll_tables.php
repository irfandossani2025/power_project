<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('payroll_day')->default(28);
            $table->string('currency', 3)->default('OMR');
            $table->decimal('omani_employee_rate', 5, 2)->default(8);
            $table->decimal('omani_employer_rate', 5, 2)->default(14.5);
            $table->decimal('non_omani_provision_rate', 5, 2)->default(0);
            $table->decimal('overtime_day_multiplier', 5, 2)->default(1.25);
            $table->decimal('overtime_night_multiplier', 5, 2)->default(1.5);
            $table->string('bank_muscat_debit_account', 64)->nullable();
            $table->string('bank_muscat_customer_number', 32)->nullable();
            $table->boolean('bank_profile_confirmed')->default(false);
            $table->timestamps();
        });
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 12, 3)->default(0);
            $table->decimal('housing_allowance', 12, 3)->default(0);
            $table->decimal('transport_allowance', 12, 3)->default(0);
            $table->decimal('other_allowance', 12, 3)->default(0);
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_account_number', 80)->nullable();
            $table->string('account_holder_name')->nullable();
            $table->timestamps();
        });
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status', 20)->default('draft');
            $table->decimal('gross_total', 14, 3)->default(0);
            $table->decimal('net_total', 14, 3)->default(0);
            $table->decimal('employer_cost_total', 14, 3)->default(0);
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'period_year', 'period_month']);
        });
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 50);
            $table->string('employee_name');
            $table->boolean('is_omani')->default(false);
            $table->decimal('basic_salary', 12, 3)->default(0);
            $table->decimal('housing_allowance', 12, 3)->default(0);
            $table->decimal('transport_allowance', 12, 3)->default(0);
            $table->decimal('other_allowance', 12, 3)->default(0);
            $table->decimal('overtime_amount', 12, 3)->default(0);
            $table->decimal('bonus_amount', 12, 3)->default(0);
            $table->decimal('unpaid_leave_deduction', 12, 3)->default(0);
            $table->decimal('advance_deduction', 12, 3)->default(0);
            $table->decimal('other_deduction', 12, 3)->default(0);
            $table->decimal('employee_contribution', 12, 3)->default(0);
            $table->decimal('employer_contribution', 12, 3)->default(0);
            $table->decimal('non_omani_provision', 12, 3)->default(0);
            $table->decimal('gross_pay', 12, 3)->default(0);
            $table->decimal('net_pay', 12, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_compensations');
        Schema::dropIfExists('payroll_settings');
    }
};
