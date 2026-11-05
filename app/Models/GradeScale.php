<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeScale extends Model
{
    protected $fillable = ['name', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradeScaleItem::class)->orderBy('min_percent');
    }
}
