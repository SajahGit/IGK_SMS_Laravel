<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    protected $fillable = [
        'code',
        'name',
        'vehicle_id',
        'monthly_fee',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TransportStop::class, 'transport_route_id')->orderBy('sequence');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class);
    }
}
