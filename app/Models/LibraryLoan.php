<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryLoan extends Model
{
    protected $fillable = ['copy_id', 'student_id', 'issued_by', 'returned_by', 'issued_at', 'due_at', 'returned_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'due_at' => 'date', 'returned_at' => 'datetime'];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(LibraryCopy::class, 'copy_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function fine(): HasOne
    {
        return $this->hasOne(LibraryFine::class, 'loan_id');
    }
}
