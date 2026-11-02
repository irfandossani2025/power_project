<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = ['company_id', 'purchase_bill_id', 'payment_date', 'amount', 'method', 'reference', 'notes'];

    protected function casts(): array { return ['payment_date' => 'date', 'amount' => 'decimal:3']; }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function purchaseBill(): BelongsTo { return $this->belongsTo(PurchaseBill::class); }
}
