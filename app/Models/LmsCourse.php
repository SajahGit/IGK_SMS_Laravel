<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsCourse extends Model
{
    protected $fillable = ['class_subject_id', 'title', 'description'];

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LmsLesson::class, 'course_id')->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LmsAssignment::class, 'course_id')->latest();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LmsCourseMaterial::class, 'course_id')->orderBy('type')->orderBy('title');
    }
}
