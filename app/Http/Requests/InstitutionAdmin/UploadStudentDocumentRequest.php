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

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:birth_cert,transcript,photo,id_card,other'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Max 5MB
        ];
    }
}
