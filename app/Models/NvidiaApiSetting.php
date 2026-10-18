<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NvidiaApiSetting extends Model
{
    protected $fillable = [
        'user_id',
        'api_key',
        'default_model',
        'is_enabled',
        'last_tested_at',
        'last_test_status',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }
}
