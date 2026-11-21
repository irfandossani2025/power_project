<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = ['company_id', 'period_year', 'period_month', 'status', 'gross_total', 'net_total', 'employer_cost_total', 'prepared_by', 'approved_by', 'approved_at', 'paid_at', 'payment_reference'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function entries()
    {
        return $this->hasMany(PayrollEntry::class);
    }
}
