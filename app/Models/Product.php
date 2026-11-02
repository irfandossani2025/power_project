<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['company_id', 'sku', 'name', 'description', 'type', 'unit', 'cost_price', 'selling_price', 'vat_rate', 'reorder_level', 'is_active'];
    protected function casts(): array { return ['cost_price' => 'decimal:3', 'selling_price' => 'decimal:3', 'vat_rate' => 'decimal:2', 'reorder_level' => 'decimal:3', 'is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
