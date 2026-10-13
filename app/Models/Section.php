<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['class_group_id', 'name', 'capacity', 'room'];

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    /** "Grade 10 - A" */
    public function getFullNameAttribute(): string
    {
        return ($this->classGroup?->name ?? '').' - '.$this->name;
    }
}
