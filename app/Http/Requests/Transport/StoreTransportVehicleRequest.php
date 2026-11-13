<?php

namespace App\Http\Requests\Transport;

use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Support\InternationalPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        InternationalPhoneNumber::normalizeRequest($this, 'driver_phone');
        if (is_string($this->input('registration_number'))) {
            $this->merge(['registration_number' => strtoupper(trim($this->input('registration_number')))]);
        }
    }

    public function authorize(): bool
    {
        $permission = $this->route('vehicle') ? 'transport.update' : 'transport.create';

        return $this->user()?->isAdmin() || $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9\s-]+$/', Rule::unique('transport_vehicles', 'registration_number')->ignore($this->route('vehicle'))],
            'vehicle_type' => ['required', 'string', 'max:40', 'regex:/^[a-zA-Z0-9\s-]+$/'],
            'capacity' => ['required', 'integer', 'between:1,500'],
            'driver_name' => ['required', 'string', 'max:80', "regex:/^[a-zA-Z\\s.\\-']+$/", new ValidPersonName],
            'driver_phone' => ['nullable', 'string', 'max:16', Rule::unique('transport_vehicles', 'driver_phone')->ignore($this->route('vehicle')), new ValidInternationalPhone($this->input('driver_phone_region'))],
            'insurance_expiry' => ['nullable', 'date'],
            'inspection_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
        ];
    }
}
