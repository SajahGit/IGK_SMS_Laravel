<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'sequence', 'start_date', 'end_date', 'is_current'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** The term everything happens in right now. */
    public static function current(): ?self
    {
        return static::with('academicYear')->where('is_current', true)->first()
            ?? static::with('academicYear')->latest('id')->first();
    }

    public function getFullNameAttribute(): string
    {
        return ($this->academicYear?->name ?? '').' - '.$this->name;
    }
}
