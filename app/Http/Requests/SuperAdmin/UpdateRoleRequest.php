<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role?->id;
        $coreRoles = ['Super Admin', 'Institution Admin', 'Registrar', 'Teacher', 'Accountant', 'Student'];

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($roleId),
                ...($role && in_array($role->name, $coreRoles, true) ? [Rule::in([$role->name])] : []),
            ],
            'guard_name' => ['required', 'string', 'max:20', 'in:web,api'],
        ];
    }
}
