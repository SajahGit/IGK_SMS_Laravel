<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Branch;
use App\Rules\ValidPersonName;
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

        if (! $this->user()->isSuperAdmin()) {
            $roleRules[] = Rule::notIn(['Super Admin']);
        }

        return [
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => $roleRules,
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
                Rule::requiredIf(fn () => $this->requiresBranchAssignment()),
                ...($this->user()->branch_id && ! $this->user()->isSuperAdmin() && ! $this->user()->isInstitutionAdmin()
                    ? [Rule::in([(int) $this->user()->branch_id])]
                    : []),
            ],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'roles' => 'role',
            'roles.0' => 'role',
            'roles.*' => 'role',
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Please select at least one role.',
            'roles.*.exists' => 'The selected role is invalid.',
            'roles.*.not_in' => 'You do not have permission to assign the selected role.',
        ];
    }

    private function requiresBranchAssignment(): bool
    {
        $roles = $this->input('roles', []);
        if (array_intersect($roles, ['Super Admin', 'Institution Admin']) !== []) {
            return false;
        }

        return Branch::query()->where('is_active', true)->exists();
    }
}
