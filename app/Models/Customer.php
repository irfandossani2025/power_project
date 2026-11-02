<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['company_id', 'name', 'company_name', 'email', 'phone', 'vat_number', 'billing_address', 'payment_terms_days', 'credit_limit', 'is_active'];
    protected function casts(): array { return ['credit_limit' => 'decimal:3', 'is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
}
