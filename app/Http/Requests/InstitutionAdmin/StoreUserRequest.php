<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Branch;
use App\Models\User;
use App\Rules\ValidPersonName;
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
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
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

    private function requiresBranchAssignment(): bool
    {
        $roles = $this->input('roles', []);
        if (array_intersect($roles, ['Super Admin', 'Institution Admin']) !== []) {
            return false;
        }

        return Branch::query()->where('is_active', true)->exists();
    }
}
