<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenAiSetting extends Model
{
    protected $table = 'openai_settings';

    protected $fillable = [
        'api_key',
        'model',
        'default_aspect_ratio',
        'default_quality',
        'is_active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected function casts(): array
    {
        return [
            // Laravel encrypts/decrypts this automatically using APP_KEY.
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }
}
