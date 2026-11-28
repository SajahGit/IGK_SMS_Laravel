<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'material_id',
        'user_id',
        'share_link_id',
        'access_type',
        'ip_address',
        'user_agent',
        'metadata',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'accessed_at' => 'datetime',
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

    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(MaterialShareLink::class, 'share_link_id');
    }
}
