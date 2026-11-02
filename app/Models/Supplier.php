<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['company_id', 'name', 'company_name', 'email', 'phone', 'vat_number', 'billing_address', 'payment_terms_days', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function purchaseBills(): HasMany { return $this->hasMany(PurchaseBill::class); }
}
