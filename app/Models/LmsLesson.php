<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsLesson extends Model
{
    protected $fillable = ['course_id', 'title', 'content', 'position', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'position' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'course_id');
    }
}
