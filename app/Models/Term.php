<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'sequence',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function subjectResults(): HasMany
    {
        return $this->hasMany(SubjectResult::class);
    }

    public function termResults(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
