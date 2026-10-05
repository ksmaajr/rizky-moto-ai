<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VercelGatewayApiKeyLog extends Model
{
    protected $table = 'vercel_gateway_api_key_logs';

    protected $fillable = [
        'vercel_gateway_api_key_id',
        'user_id',
        'status',
        'message',
        'detail',
        'http_status',
        'duration_ms',
        'error_type',
        'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'tested_at' => 'datetime',
        ];
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(VercelGatewayApiKey::class, 'vercel_gateway_api_key_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
