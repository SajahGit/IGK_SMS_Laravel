<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('roles') && $this->filled('role')) {
            $this->merge(['roles' => [$this->input('role')]]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                'exists:roles,name',
                Rule::notIn(['Student', 'student']),
                ...($this->user()->isSuperAdmin() ? [] : [Rule::notIn(['Super Admin'])]),
            ],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }
}
