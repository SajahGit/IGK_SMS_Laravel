<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UploadStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && (auth()->user()->isAdmin() || auth()->user()->can('student_documents.create') || auth()->user()->isStudent());
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => 'other']);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:50'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // Max 10MB
        ];
    }
}
