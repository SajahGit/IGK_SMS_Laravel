<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScaleItem extends Model
{
    protected $fillable = [
        'grade_scale_id',
        'min_percent',
        'max_percent',
        'grade',
        'grade_point',
        'is_pass',
    ];

    protected function casts(): array
    {
        return [
            'min_percent' => 'decimal:2',
            'max_percent' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'is_pass' => 'boolean',
        ];
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }
}
