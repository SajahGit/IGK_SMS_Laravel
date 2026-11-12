<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('vehicle') ? 'transport.update' : 'transport.create';

        return $this->user()?->isAdmin() || $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:30', Rule::unique('transport_vehicles', 'registration_number')->ignore($this->route('vehicle'))],
            'vehicle_type' => ['required', 'string', 'max:40'],
            'capacity' => ['required', 'integer', 'between:1,500'],
            'driver_name' => ['required', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'insurance_expiry' => ['nullable', 'date'],
            'inspection_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
        ];
    }
}
