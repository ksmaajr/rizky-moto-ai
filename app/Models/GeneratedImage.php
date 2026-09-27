<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'generation_id',
        'image_path',
        'image_url',
        'model',
        'format',
        'width',
        'height',
        'metadata',
        'is_primary',
        'is_favorite',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_primary' => 'boolean',
        'is_favorite' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}