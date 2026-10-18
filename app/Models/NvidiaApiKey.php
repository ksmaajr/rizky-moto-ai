<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NvidiaApiKey extends Model
{
    protected $table = 'nvidia_api_keys';

    protected $fillable = [
        'user_id', 'name', 'api_key', 'status', 'request_count',
        'success_count', 'failure_count', 'last_error_type', 'last_error',
        'last_http_status', 'last_used_at', 'last_success_at',
        'last_failure_at', 'cooldown_until', 'is_active', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'cooldown_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMaskedKeyAttribute(): string
    {
        $key = (string) $this->api_key;
        if ($key === '') return 'nvapi-••••••••';
        return substr($key, 0, min(6, strlen($key))) . '••••••••' . (strlen($key) > 10 ? substr($key, -4) : '');
    }
}
