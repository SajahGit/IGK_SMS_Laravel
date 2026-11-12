<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    protected $fillable = ['name', 'shared_secret', 'is_active', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'shared_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    protected $hidden = ['shared_secret'];

    public function events(): HasMany
    {
        return $this->hasMany(BiometricEvent::class);
    }
}
