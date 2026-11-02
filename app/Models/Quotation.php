<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = ['company_id', 'customer_id', 'quotation_number', 'quotation_date', 'valid_until', 'status', 'currency', 'subtotal', 'discount_total', 'vat_total', 'total', 'notes', 'terms'];
    protected function casts(): array { return ['quotation_date' => 'date', 'valid_until' => 'date', 'subtotal' => 'decimal:3', 'discount_total' => 'decimal:3', 'vat_total' => 'decimal:3', 'total' => 'decimal:3']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class)->orderBy('sort_order'); }
}
