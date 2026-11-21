<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/** Adds only clearly fictional HR, payroll and fleet records without touching accounting data. */
class DemoHrmsPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrFail();
        $today = now()->startOfDay();
        $employees = collect([
            ['employee_number' => 'DEMO-EMP-001', 'full_name' => 'Ahmed Al Harthy', 'nationality' => 'Omani', 'is_omani' => true, 'department' => 'Operations', 'job_title' => 'Operations Supervisor', 'phone' => '+968 9500 1001', 'joining_date' => $today->copy()->subYears(3), 'omani_id_number' => 'OM-DEMO-1001', 'omani_id_expiry_date' => $today->copy()->addDays(38), 'is_active' => true, 'notes' => 'Fictional demonstration employee.'],
            ['employee_number' => 'DEMO-EMP-002', 'full_name' => 'Maria Santos', 'nationality' => 'Philippines', 'is_omani' => false, 'department' => 'Finance', 'job_title' => 'Accounts Assistant', 'phone' => '+968 9500 1002', 'joining_date' => $today->copy()->subYears(2), 'visa_number' => 'V-DEMO-2002', 'visa_expiry_date' => $today->copy()->addDays(45), 'resident_card_number' => 'RC-DEMO-2002', 'resident_card_expiry_date' => $today->copy()->addDays(90), 'passport_number' => 'P-DEMO-2002', 'passport_expiry_date' => $today->copy()->addMonths(11), 'is_active' => true, 'notes' => 'Fictional demonstration employee.'],
            ['employee_number' => 'DEMO-EMP-003', 'full_name' => 'Ramesh Kumar', 'nationality' => 'India', 'is_omani' => false, 'department' => 'Projects', 'job_title' => 'Site Electrician', 'phone' => '+968 9500 1003', 'joining_date' => $today->copy()->subYear(), 'visa_number' => 'V-DEMO-2003', 'visa_expiry_date' => $today->copy()->addMonths(7), 'resident_card_number' => 'RC-DEMO-2003', 'resident_card_expiry_date' => $today->copy()->addMonths(4), 'passport_number' => 'P-DEMO-2003', 'passport_expiry_date' => $today->copy()->addDays(50), 'is_active' => true, 'notes' => 'Fictional demonstration employee.'],
        ])->map(fn (array $data) => Employee::updateOrCreate(['company_id' => $company->id, 'employee_number' => $data['employee_number']], $data + ['company_id' => $company->id]));

        foreach ([[850, 180, 70, 0], [420, 160, 60, 0], [350, 120, 50, 0]] as $i => $salary) {
            EmployeeCompensation::updateOrCreate(['employee_id' => $employees[$i]->id], ['basic_salary' => $salary[0], 'housing_allowance' => $salary[1], 'transport_allowance' => $salary[2], 'other_allowance' => $salary[3], 'bank_name' => 'Bank Muscat', 'bank_account_number' => 'DEMO-ACCOUNT-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'account_holder_name' => $employees[$i]->full_name]);
        }
        PayrollSetting::firstOrCreate(['company_id' => $company->id]);
        $run = PayrollRun::updateOrCreate(['company_id' => $company->id, 'period_year' => $today->year, 'period_month' => $today->month], ['status' => 'draft', 'prepared_by' => null]);
        $run->entries()->delete();
        foreach ($employees as $employee) {
            $compensation = $employee->compensation;
            $gross = $compensation->basic_salary + $compensation->housing_allowance + $compensation->transport_allowance + $compensation->other_allowance;
            $contribution = $employee->is_omani ? round($compensation->basic_salary * .08, 3) : 0;
            PayrollEntry::create(['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'employee_number' => $employee->employee_number, 'employee_name' => $employee->full_name, 'is_omani' => $employee->is_omani, 'basic_salary' => $compensation->basic_salary, 'housing_allowance' => $compensation->housing_allowance, 'transport_allowance' => $compensation->transport_allowance, 'other_allowance' => $compensation->other_allowance, 'employee_contribution' => $contribution, 'employer_contribution' => $employee->is_omani ? round($compensation->basic_salary * .145, 3) : 0, 'gross_pay' => $gross, 'net_pay' => $gross - $contribution, 'remarks' => 'Fictional demo payroll entry']);
        }
        $run->update(['gross_total' => $run->entries()->sum('gross_pay'), 'net_total' => $run->entries()->sum('net_pay'), 'employer_cost_total' => $run->entries()->sum('employer_contribution')]);

        foreach ([
            ['vehicle_number' => 'DEMO-VEH-001', 'registration_plate' => 'DEMO 1842', 'make' => 'Toyota', 'model' => 'Hilux', 'model_year' => 2023, 'colour' => 'White', 'assigned_to' => 'Project Delivery Team', 'mulkiya_number' => 'M-DEMO-001', 'mulkiya_expiry_date' => $today->copy()->addDays(28), 'insurance_provider' => 'Demo Insurance', 'insurance_policy_number' => 'INS-DEMO-001', 'insurance_expiry_date' => $today->copy()->addDays(56), 'inspection_expiry_date' => $today->copy()->addMonths(5)],
            ['vehicle_number' => 'DEMO-VEH-002', 'registration_plate' => 'DEMO 3097', 'make' => 'Nissan', 'model' => 'Urvan', 'model_year' => 2022, 'colour' => 'Silver', 'assigned_to' => 'Operations Team', 'mulkiya_number' => 'M-DEMO-002', 'mulkiya_expiry_date' => $today->copy()->addMonths(7), 'insurance_provider' => 'Demo Insurance', 'insurance_policy_number' => 'INS-DEMO-002', 'insurance_expiry_date' => $today->copy()->addDays(40), 'inspection_expiry_date' => $today->copy()->addDays(75)],
        ] as $data) {
            Vehicle::updateOrCreate(['company_id' => $company->id, 'vehicle_number' => $data['vehicle_number']], $data + ['company_id' => $company->id, 'is_active' => true, 'notes' => 'Fictional demonstration vehicle.']);
        }
    }
}
