<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransportStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->can('transport.update') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'sequence' => ['required', 'integer', 'between:1,500'],
            'address' => ['nullable', 'string', 'max:500'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'dropoff_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
