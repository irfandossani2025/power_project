<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiActivityLog extends Model
{
    protected $fillable = ['company_id', 'user_id', 'provider', 'action', 'prompt', 'response', 'context_snapshot', 'suggested_actions', 'status', 'approval_required', 'approved_at'];

    protected function casts(): array
    {
        return ['context_snapshot' => 'array', 'suggested_actions' => 'array', 'approval_required' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
