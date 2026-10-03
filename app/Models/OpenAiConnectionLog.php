<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenAiConnectionLog extends Model
{
    protected $table = 'openai_connection_logs';

    protected $fillable = [
        'user_id',
        'status',
        'message',
        'detail',
        'http_status',
        'duration_ms',
        'model',
        'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
