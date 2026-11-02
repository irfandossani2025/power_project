<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBillItem extends Model
{
    protected $fillable = ['purchase_bill_id', 'product_id', 'sort_order', 'description', 'quantity', 'unit', 'unit_cost', 'vat_rate', 'line_subtotal', 'line_vat', 'line_total'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:3', 'vat_rate' => 'decimal:2', 'line_subtotal' => 'decimal:3', 'line_vat' => 'decimal:3', 'line_total' => 'decimal:3']; }
    public function purchaseBill(): BelongsTo { return $this->belongsTo(PurchaseBill::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
