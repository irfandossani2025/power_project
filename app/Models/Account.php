<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable=['company_id','code','name','type','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function journalLines(): HasMany { return $this->hasMany(JournalLine::class); }
}
