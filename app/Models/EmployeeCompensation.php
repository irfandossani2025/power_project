<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';

    protected $fillable = ['employee_id', 'basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowance', 'bank_name', 'bank_account_number', 'account_holder_name'];
}
