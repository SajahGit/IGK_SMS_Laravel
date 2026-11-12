<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsAssignment extends Model
{
    protected $fillable = ['course_id', 'title', 'instructions', 'due_at', 'max_points', 'published_at'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_points' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'course_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LmsSubmission::class, 'assignment_id');
    }
}
