<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLibraryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('library.create'));
    }

    public function rules(): array
    {
        return [
            'copy_id' => ['required', 'integer', 'exists:library_copies,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'due_at' => ['required', 'date', 'after:today'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $student = \App\Models\Student::find($this->integer('student_id'));
                if (! $student || $student->status !== 'active') {
                    $validator->errors()->add('student_id', 'Only active students may borrow library books.');
                }
            },
        ];
    }
}
