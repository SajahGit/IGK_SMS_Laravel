<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    protected $fillable = [
        'hostel_id',
        'room_number',
        'bed_capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bed_capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HostelAssignment::class);
    }
}
