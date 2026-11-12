<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricEvent extends Model
{
    protected $fillable = ['biometric_device_id', 'event_id', 'admission_no', 'student_id', 'scanned_at', 'payload', 'status'];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime', 'payload' => 'array'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
