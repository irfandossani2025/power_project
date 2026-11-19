<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistantSetting extends Model
{
    protected $fillable = ['company_id', 'enabled', 'provider', 'model', 'api_key', 'company_guidance', 'allow_draft_actions', 'monthly_request_limit'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'api_key' => 'encrypted', 'allow_draft_actions' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
