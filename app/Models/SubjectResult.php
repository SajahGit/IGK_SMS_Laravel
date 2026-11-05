<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectResult extends Model
{
    protected $fillable = [
        'term_id',
        'class_id',
        'student_id',
        'subject_id',
        'percentage',
        'grade',
        'grade_point',
        'credit_hours',
        'result_status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'credit_hours' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
