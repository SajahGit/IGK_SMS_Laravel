<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermResult extends Model
{
    protected $fillable = [
        'term_id',
        'class_id',
        'student_id',
        'gpa',
        'result_status',
        'publication_status',
        'submitted_by',
        'approved_by',
        'published_by',
        'unpublish_reason',
        'generated_at',
        'submitted_at',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'gpa' => 'decimal:2',
            'generated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
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
}
