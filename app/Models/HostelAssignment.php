<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAssignment extends Model
{
    protected $fillable = [
        'student_id',
        'hostel_room_id',
        'academic_year_id',
        'bed_number',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'bed_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
