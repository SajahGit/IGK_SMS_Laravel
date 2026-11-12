<?php

namespace App\Models;

use App\Models\Scopes\BranchOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = ['name', 'section', 'capacity', 'program_id', 'academic_year_id', 'branch_id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchOwned);
    }

    protected $attributes = [
        'capacity' => 40,
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->name.' - '.$this->section;
    }
}
