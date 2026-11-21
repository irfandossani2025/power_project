<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = ['company_id', 'payroll_day', 'currency', 'omani_employee_rate', 'omani_employer_rate', 'non_omani_provision_rate', 'overtime_day_multiplier', 'overtime_night_multiplier', 'bank_muscat_debit_account', 'bank_muscat_customer_number', 'bank_profile_confirmed'];

    protected function casts(): array
    {
        return ['bank_profile_confirmed' => 'boolean'];
    }
}
