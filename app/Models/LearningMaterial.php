<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMaterial extends Model
{
    public const TYPE_VIDEO = 'video';
    public const TYPE_PDF = 'pdf';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const VISIBILITY_LIBRARY = 'library';
    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'title',
        'description',
        'material_type',
        'storage_path',
        'original_name',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'category_id',
        'course_subject_label',
        'uploaded_by',
        'visibility',
        'status',
        'allow_download',
        'expiry_enabled',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'allow_download' => 'boolean',
            'expiry_enabled' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(MaterialShareLink::class, 'material_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(MaterialAccessLog::class, 'material_id');
    }

    public function videoProgresses(): HasMany
    {
        return $this->hasMany(VideoProgress::class, 'material_id');
    }

    public function isVideo(): bool
    {
        return $this->material_type === self::TYPE_VIDEO;
    }

    public function isPdf(): bool
    {
        return $this->material_type === self::TYPE_PDF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->expiry_enabled
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->isActive() && ! $this->isExpired();
    }

    public function scopeLibraryVisible(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('visibility', self::VISIBILITY_LIBRARY)
            ->where(function (Builder $q) {
                $q->where('expiry_enabled', false)
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpiredOnly(Builder $query): Builder
    {
        return $query
            ->where('expiry_enabled', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
