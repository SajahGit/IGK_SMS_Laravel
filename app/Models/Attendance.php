<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    // "date" is deliberately NOT cast, so updateOrCreate(['date' => 'Y-m-d']) always matches.
    protected $fillable = ['student_id', 'class_id', 'date', 'status', 'marked_by'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
