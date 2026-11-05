<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('curriculum.update');
    }

    public function rules(): array
    {
        return [
            'program_id' => ['sometimes', 'required', 'exists:programs,id'],
            'subject_id' => ['sometimes', 'required', 'exists:subjects,id'],
            'term_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'is_elective' => ['nullable', 'boolean'],
        ];
    }

    public function passedValidation(): void
    {
        $curriculum = $this->route('curriculum');

        $programId = $this->input('program_id', $curriculum->program_id);
        $subjectId = $this->input('subject_id', $curriculum->subject_id);
        $termNumber = $this->input('term_number', $curriculum->term_number);

        if (Curriculum::where('program_id', $programId)
            ->where('subject_id', $subjectId)
            ->where('term_number', $termNumber)
            ->where('id', '!=', $curriculum->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => 'This subject is already assigned to the program for this term.',
            ]);
        }
    }
}
