<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsCourseMaterial extends Model
{
    protected $fillable = [
        'course_id',
        'type',
        'title',
        'description',
        'file_path',
        'original_name',
        'uploaded_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
