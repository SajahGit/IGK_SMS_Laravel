<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryBook extends Model
{
    use SoftDeletes;

    protected $fillable = ['author_id', 'category_id', 'title', 'isbn', 'publication_year'];

    protected function casts(): array
    {
        return ['publication_year' => 'integer'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(LibraryAuthor::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'category_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(LibraryCopy::class, 'book_id');
    }
}
