<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('curriculum.create');
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'exists:programs,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'term_number' => ['required', 'integer', 'min:1', 'max:12'],
            'is_elective' => ['nullable', 'boolean'],
        ];
    }

    public function passedValidation(): void
    {
        $programId = (int) $this->input('program_id');
        $subjectId = (int) $this->input('subject_id');
        $termNumber = (int) $this->input('term_number');

        if (Curriculum::where('program_id', $programId)
            ->where('subject_id', $subjectId)
            ->where('term_number', $termNumber)
            ->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => 'This subject is already assigned to the program for this term.',
            ]);
        }
    }
}
