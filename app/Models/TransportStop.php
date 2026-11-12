<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportStop extends Model
{
    protected $fillable = [
        'transport_route_id',
        'name',
        'sequence',
        'address',
        'pickup_time',
        'dropoff_time',
    ];

    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'transport_stop_id');
    }
}
