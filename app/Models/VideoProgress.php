<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    public const CREATED_AT = null;

    protected $table = 'video_progress';

    protected $fillable = [
        'material_id',
        'user_id',
        'last_position',
        'duration',
        'completed',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_position' => 'integer',
            'duration' => 'integer',
            'completed' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
