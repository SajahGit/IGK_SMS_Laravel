<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->program);
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->id;

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('programs', 'code')->ignore($programId)],
            'duration_terms' => ['nullable', 'integer', 'min:1'],
            'level' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
