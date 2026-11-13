<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportRouteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    public function authorize(): bool
    {
        $permission = $this->route('transportRoute') ? 'transport.update' : 'transport.create';

        return $this->user()?->isAdmin() || $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:25', 'regex:/^[A-Z0-9-]+$/', Rule::unique('transport_routes', 'code')->ignore($this->route('transportRoute'))],
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9\s()-]+$/'],
            'vehicle_id' => ['nullable', 'integer', 'exists:transport_vehicles,id'],
            'monthly_fee' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
