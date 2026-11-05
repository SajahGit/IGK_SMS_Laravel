<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Curriculum extends Model
{
    protected $fillable = [
        'program_id',
        'subject_id',
        'term_number',
        'is_elective',
    ];

    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
            'is_elective' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
