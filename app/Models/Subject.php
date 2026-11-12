<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'credit_hours'];

    protected $attributes = [
        'credit_hours' => 3,
    ];

    protected function casts(): array
    {
        return [
            'credit_hours' => 'integer',
        ];
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'curricula')
            ->withPivot('term_number', 'is_elective')
            ->withTimestamps();
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function subjectResults(): HasMany
    {
        return $this->hasMany(SubjectResult::class);
    }
}
