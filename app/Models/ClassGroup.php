<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassGroup extends Model
{
    protected $fillable = ['name', 'level'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
