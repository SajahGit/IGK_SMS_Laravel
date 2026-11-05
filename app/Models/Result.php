<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = ['exam_id', 'student_id', 'marks', 'is_absent', 'is_excused'];

    protected $attributes = [
        'is_absent' => false,
        'is_excused' => false,
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'decimal:2',
            'is_absent' => 'boolean',
            'is_excused' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
