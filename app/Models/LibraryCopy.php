<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryCopy extends Model
{
    use SoftDeletes;

    protected $fillable = ['book_id', 'copy_number', 'accession_no', 'condition', 'status'];

    protected function casts(): array
    {
        return ['copy_number' => 'integer'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LibraryLoan::class, 'copy_id');
    }
}
