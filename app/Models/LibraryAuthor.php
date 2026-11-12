<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryAuthor extends Model
{
    protected $fillable = ['name'];

    public function books(): HasMany
    {
        return $this->hasMany(LibraryBook::class, 'author_id');
    }
}
