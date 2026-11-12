<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStaffAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('staff_attendance.create'));
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('check_in') && $this->filled('check_out')
                    && $this->input('check_out') <= $this->input('check_in')) {
                    $validator->errors()->add('check_out', 'Check-out time must be after check-in time.');
                }
            },
        ];
    }
}
