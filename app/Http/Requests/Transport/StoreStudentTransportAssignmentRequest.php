<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentTransportAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->can('transport.assign') === true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'transport_route_id' => ['required', 'integer', 'exists:transport_routes,id'],
            'transport_stop_id' => ['required', 'integer', 'exists:transport_stops,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
