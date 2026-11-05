<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'class_id',
        'subject_id',
        'term_id',
        'exam_date',
        'max_marks',
        'weight',
        'created_by',
        'is_published',
        'published_at',
    ];

    protected $attributes = [
        'type' => 'final',
        'max_marks' => 100,
        'weight' => 100,
        'is_published' => false,
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'max_marks' => 'integer',
            'weight' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
