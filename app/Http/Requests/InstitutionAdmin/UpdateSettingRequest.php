<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->setting);
    }

    public function rules(): array
    {
        $settingId = $this->route('setting')?->id;

        return [
            'key' => ['required', 'string', 'max:100', Rule::unique('settings', 'key')->ignore($settingId)],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:string,integer,float,boolean'],
            'group' => ['nullable', 'string', 'max:50'],
        ];
    }
}
