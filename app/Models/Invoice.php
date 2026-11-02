<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['company_id', 'customer_id', 'quotation_id', 'invoice_number', 'invoice_date', 'due_date', 'status', 'currency', 'subtotal', 'discount_total', 'vat_total', 'total', 'paid_total', 'balance_due', 'notes', 'terms'];

    protected function casts(): array
    {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:3', 'discount_total' => 'decimal:3', 'vat_total' => 'decimal:3', 'total' => 'decimal:3', 'paid_total' => 'decimal:3', 'balance_due' => 'decimal:3'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class)->orderBy('sort_order'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->latest('payment_date'); }
}
