<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'product_id', 'sort_order', 'description', 'quantity', 'unit', 'unit_price', 'discount_percentage', 'vat_rate', 'line_subtotal', 'line_vat', 'line_total'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:3', 'discount_percentage' => 'decimal:2', 'vat_rate' => 'decimal:2', 'line_subtotal' => 'decimal:3', 'line_vat' => 'decimal:3', 'line_total' => 'decimal:3']; }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
