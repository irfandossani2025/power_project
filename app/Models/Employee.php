<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['company_id', 'employee_number', 'full_name', 'nationality', 'is_omani', 'department', 'job_title', 'phone', 'email', 'joining_date', 'visa_number', 'visa_expiry_date', 'resident_card_number', 'resident_card_expiry_date', 'passport_number', 'passport_expiry_date', 'omani_id_number', 'omani_id_expiry_date', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_omani' => 'boolean', 'is_active' => 'boolean', 'joining_date' => 'date', 'visa_expiry_date' => 'date', 'resident_card_expiry_date' => 'date', 'passport_expiry_date' => 'date', 'omani_id_expiry_date' => 'date'];
    }
}
