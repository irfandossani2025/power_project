<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    protected $fillable = ['payroll_run_id', 'employee_id', 'employee_number', 'employee_name', 'is_omani', 'basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowance', 'overtime_amount', 'bonus_amount', 'unpaid_leave_deduction', 'advance_deduction', 'other_deduction', 'employee_contribution', 'employer_contribution', 'non_omani_provision', 'gross_pay', 'net_pay', 'remarks'];

    protected function casts(): array
    {
        return ['is_omani' => 'boolean'];
    }

    public function run()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
