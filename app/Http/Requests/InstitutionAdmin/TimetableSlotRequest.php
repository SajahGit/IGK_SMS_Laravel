<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Support\TimetableClashDetector;
use Illuminate\Foundation\Http\FormRequest;

class TimetableSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'class_subject_id' => ['required', 'exists:class_subject,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['required', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('start_time') && is_string($this->start_time)) {
            $this->merge(['start_time' => substr($this->start_time, 0, 5)]);
        }
        if ($this->has('end_time') && is_string($this->end_time)) {
            $this->merge(['end_time' => substr($this->end_time, 0, 5)]);
        }
    }

    public function passedValidation(): void
    {
        $slot = $this->route('timetableSlot');
        $ignoreId = $slot ? (is_object($slot) ? $slot->id : (int) $slot) : null;

        TimetableClashDetector::checkClash(
            (int) $this->input('class_subject_id'),
            (int) $this->input('day_of_week'),
            (string) $this->input('start_time'),
            (string) $this->input('end_time'),
            (string) $this->input('room'),
            $ignoreId
        );
    }
}
