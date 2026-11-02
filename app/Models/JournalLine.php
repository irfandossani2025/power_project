<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $fillable=['journal_entry_id','account_id','description','debit','credit'];
    protected function casts(): array { return ['debit'=>'decimal:3','credit'=>'decimal:3']; }
    public function entry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
