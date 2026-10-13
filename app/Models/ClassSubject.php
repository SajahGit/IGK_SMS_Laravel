<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubject extends Model
{
    protected $fillable = ['section_id', 'subject_id', 'term_id', 'staff_id'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** "Grade 10 - A / Mathematics" */
    public function getLabelAttribute(): string
    {
        return ($this->section?->full_name ?? '').' / '.($this->subject?->name ?? '');
    }
}
