<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['company_id', 'product_id', 'warehouse_id', 'type', 'quantity', 'unit_cost', 'movement_date', 'reference', 'notes', 'created_by'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:3', 'movement_date' => 'date']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
