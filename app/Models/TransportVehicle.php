<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportVehicle extends Model
{
    protected $fillable = [
        'registration_number',
        'vehicle_type',
        'capacity',
        'driver_name',
        'driver_phone',
        'insurance_expiry',
        'inspection_expiry',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'insurance_expiry' => 'date',
            'inspection_expiry' => 'date',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class, 'vehicle_id');
    }
}
