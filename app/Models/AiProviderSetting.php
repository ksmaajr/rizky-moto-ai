<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderSetting extends Model
{
    protected $fillable = [
        'user_id',
        'active_provider',
        'fallback_provider',
        'allow_provider_fallback',
        'emergency_fallback',
    ];

    protected function casts(): array
    {
        return [
            'allow_provider_fallback' => 'boolean',
            'emergency_fallback' => 'boolean',
        ];
    }
}
