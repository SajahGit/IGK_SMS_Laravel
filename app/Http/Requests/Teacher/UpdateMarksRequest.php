<?php

namespace App\Http\Requests\Teacher;

use App\Models\Exam;
use App\Services\ResultPublicationService;
use App\Support\SchoolHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isTeacher() || auth()->user()->isAdmin());
    }

    public function rules(): array
    {
        /** @var Exam $exam */
        $exam = $this->route('exam');

        return [
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0', 'max:'.$exam->max_marks],
            'is_absent' => ['nullable', 'array'],
            'is_absent.*' => ['nullable', 'boolean'],
            'is_excused' => ['nullable', 'array'],
            'is_excused.*' => ['nullable', 'boolean'],
        ];
    }

    public function passedValidation(): void
    {
        /** @var Exam $exam */
        $exam = $this->route('exam');

        if (auth()->user()->isTeacher()) {
            SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $exam->class_id);
        }

        // Enforce lock if published
        ResultPublicationService::verifyNotLocked($exam);
    }
}
