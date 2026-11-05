<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;

class TermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50'],
            'sequence' => ['required', 'integer', 'min:1', 'max:10'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
