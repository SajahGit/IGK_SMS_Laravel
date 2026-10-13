<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectResult extends Model
{
    protected $fillable = [
        'exam_id', 'exam_subject_id', 'class_subject_id', 'student_id', 'marks_obtained', 'max_marks',
        'percentage', 'grade', 'grade_point', 'is_pass', 'is_absent',
    ];

    protected function casts(): array
    {
        return ['is_pass' => 'boolean', 'is_absent' => 'boolean'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
