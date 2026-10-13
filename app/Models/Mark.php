<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    protected $fillable = ['exam_subject_id', 'student_id', 'marks_obtained', 'is_absent', 'entered_by'];

    protected function casts(): array
    {
        return ['marks_obtained' => 'decimal:2', 'is_absent' => 'boolean'];
    }

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
