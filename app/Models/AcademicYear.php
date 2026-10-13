<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first() ?? static::latest('id')->first();
    }
}
