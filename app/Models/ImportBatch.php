<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    protected $fillable = ['company_id', 'created_by', 'type', 'original_filename', 'status', 'rows_read', 'imported_count', 'skipped_count', 'errors'];
    protected function casts(): array { return ['errors' => 'array']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
