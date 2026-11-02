<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseBill extends Model
{
    protected $fillable = ['company_id', 'supplier_id', 'bill_number', 'bill_date', 'due_date', 'status', 'currency', 'subtotal', 'vat_total', 'total', 'paid_total', 'balance_due', 'notes'];
    protected function casts(): array { return ['bill_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:3', 'vat_total' => 'decimal:3', 'total' => 'decimal:3', 'paid_total' => 'decimal:3', 'balance_due' => 'decimal:3']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseBillItem::class)->orderBy('sort_order'); }
}
