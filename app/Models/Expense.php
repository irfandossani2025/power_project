<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['company_id', 'project_id', 'expense_date', 'category', 'payee', 'reference', 'description', 'payment_method', 'amount_before_vat', 'vat_rate', 'vat_amount', 'total', 'notes', 'created_by'];
    protected function casts(): array { return ['expense_date' => 'date', 'amount_before_vat' => 'decimal:3', 'vat_rate' => 'decimal:2', 'vat_amount' => 'decimal:3', 'total' => 'decimal:3']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
