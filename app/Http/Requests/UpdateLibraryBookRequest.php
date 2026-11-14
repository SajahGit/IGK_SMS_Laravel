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
            'title' => ['required', 'string', 'max:120', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-:()\'&]*\z/'],
            'author' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z\s.\'-]+\z/'],
            'category' => ['required', 'string', 'max:120', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-:()\'&]*\z/'],
            'isbn' => ['nullable', 'string', 'min:10', 'max:17', 'regex:/\A[0-9X-]+\z/', Rule::unique('library_books', 'isbn')->ignore($book instanceof LibraryBook ? $book->id : null)],
            'publication_year' => ['nullable', 'integer', 'between:1800,2026'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $isbn = $this->input('isbn');
        if (is_string($isbn)) {
            $this->merge(['isbn' => strtoupper($isbn)]);
        }
    }
}
