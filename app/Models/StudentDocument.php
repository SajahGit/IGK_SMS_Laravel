<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'original_name',
        'path',
        'mime',
        'size',
        'uploaded_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getReadableSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'birth_cert' => 'Birth Certificate',
            'transcript' => 'Academic Transcript',
            'photo' => 'Student Photograph',
            'id_card' => 'Identity Card / Passport',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
