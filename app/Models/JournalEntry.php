<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable=['company_id','source_type','source_id','entry_number','entry_date','reference','memo','created_by'];
    protected function casts(): array { return ['entry_date'=>'date']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function lines(): HasMany { return $this->hasMany(JournalLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
