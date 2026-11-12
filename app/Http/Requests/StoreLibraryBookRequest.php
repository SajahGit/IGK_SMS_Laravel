<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLibraryBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('library.create'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('library_books', 'isbn')],
            'publication_year' => ['nullable', 'integer', 'between:1000,'.(now()->year + 1)],
            'copies' => ['required', 'integer', 'between:1,100'],
        ];
    }
}
