<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['company_id', 'name', 'code', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
}
