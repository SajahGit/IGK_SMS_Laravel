<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('roles') && $this->filled('role')) {
            $this->merge(['roles' => [$this->input('role')]]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user);
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $user = $this->route('user');
        $roleRules = ['required', 'string', 'distinct', 'exists:roles,name'];

        if (! $user?->student) {
            $roleRules[] = Rule::notIn(['Student', 'student']);
        }
        if (! $this->user()->isSuperAdmin()) {
            $roleRules[] = Rule::notIn(['Super Admin']);
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => $roleRules,
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }
}
