<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'legal_name', 'vat_number', 'currency', 'phone', 'email', 'address'];

    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function warehouses(): HasMany { return $this->hasMany(Warehouse::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function suppliers(): HasMany { return $this->hasMany(Supplier::class); }
    public function purchaseBills(): HasMany { return $this->hasMany(PurchaseBill::class); }
}
