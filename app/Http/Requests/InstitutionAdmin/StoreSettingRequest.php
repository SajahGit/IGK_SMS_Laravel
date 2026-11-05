<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', Rule::unique('settings', 'key')],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:string,integer,float,boolean'],
            'group' => ['nullable', 'string', 'max:50'],
        ];
    }
}
