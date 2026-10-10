<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'email',
        'access_token',
        'is_active',
        'status',
        'request_count',
        'success_count',
        'failure_count',
        'last_used_at',
        'last_success_at',
        'last_failure_at',
        'cooldown_until',
        'cooldown_duration_minutes',
        'last_error_type',
        'last_error',
        'last_exit_code',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
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
}
