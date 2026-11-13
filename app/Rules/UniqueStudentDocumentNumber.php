<?php

namespace App\Rules;

use App\Models\Student;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueStudentDocumentNumber implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreStudentId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $exists = Student::query()
            ->withoutGlobalScopes()
            ->where(function ($query) use ($value) {
                $query->whereRaw('UPPER(document_number) = ?', [strtoupper(trim($value))])
                    ->orWhereRaw('UPPER(national_id) = ?', [strtoupper(trim($value))]);
            })
            ->when($this->ignoreStudentId, fn ($query) => $query->where('students.id', '!=', $this->ignoreStudentId))
            ->exists();

        if ($exists) {
            $fail('This document number is already assigned to another student.');
        }
    }
}
