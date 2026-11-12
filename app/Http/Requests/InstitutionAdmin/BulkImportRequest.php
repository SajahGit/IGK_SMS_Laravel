<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('imports.create'));
    }

    public function rules(): array
    {
        return [
            'entity' => ['required', 'in:students,staff,subjects,guardians,inventory,library_books'],
            'mode' => ['required', 'in:dry_run,commit'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
