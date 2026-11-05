<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')],
            'head_staff_id' => ['nullable', 'exists:staff,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
