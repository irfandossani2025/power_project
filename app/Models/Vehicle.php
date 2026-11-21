<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = ['company_id', 'vehicle_number', 'registration_plate', 'make', 'model', 'model_year', 'colour', 'assigned_to', 'mulkiya_number', 'mulkiya_expiry_date', 'insurance_provider', 'insurance_policy_number', 'insurance_expiry_date', 'inspection_expiry_date', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'mulkiya_expiry_date' => 'date', 'insurance_expiry_date' => 'date', 'inspection_expiry_date' => 'date'];
    }
}
