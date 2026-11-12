<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('transportRoute') ? 'transport.update' : 'transport.create';

        return $this->user()?->isAdmin() || $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('transport_routes', 'code')->ignore($this->route('transportRoute'))],
            'name' => ['required', 'string', 'max:120'],
            'vehicle_id' => ['nullable', 'integer', 'exists:transport_vehicles,id'],
            'monthly_fee' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
