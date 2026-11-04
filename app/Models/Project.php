<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['company_id', 'customer_id', 'project_number', 'name', 'site_location', 'manager_name', 'status', 'start_date', 'target_end_date', 'budget', 'description', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'target_end_date' => 'date', 'budget' => 'decimal:3'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function purchaseBills(): HasMany { return $this->hasMany(PurchaseBill::class); }
    public function expenses(): HasMany { return $this->hasMany(Expense::class); }
}
