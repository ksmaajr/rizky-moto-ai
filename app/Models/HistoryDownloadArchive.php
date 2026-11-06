<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class HistoryDownloadArchive extends Model
{
    protected $fillable = [
        'user_id', 'generation_ids', 'status', 'total_images',
        'processed_images', 'file_path', 'file_name', 'error_message',
    ];

    protected $casts = [
        'generation_ids' => 'array',
        'total_images' => 'integer',
        'processed_images' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
