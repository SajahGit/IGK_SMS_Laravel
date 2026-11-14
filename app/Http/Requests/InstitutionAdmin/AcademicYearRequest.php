<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $yearId = $this->route('academicYear')?->id ?? $this->route('academicYear');

        return [
            'name' => [
                'required',
                'string',
                'max:9',
                'regex:/\A[0-9]{4}(?:-[0-9]{4})?\z/',
                Rule::unique('academic_years', 'name')->ignore($yearId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
