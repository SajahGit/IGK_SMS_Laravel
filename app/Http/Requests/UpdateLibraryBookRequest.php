<?php

namespace App\Http\Requests;

use App\Models\LibraryBook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('library.update'));
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('library_books', 'isbn')->ignore($book instanceof LibraryBook ? $book->id : null)],
            'publication_year' => ['nullable', 'integer', 'between:1000,'.(now()->year + 1)],
        ];
    }
}
