<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('students.update'));
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = $student instanceof Student ? $student->id : $student;
        $userId = $student instanceof Student ? $student->user_id : Student::findOrFail($studentId)->user_id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', Password::defaults()],
            'class_id' => ['required', 'exists:classes,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'admission_no' => ['required', 'string', 'regex:/^\d{4}-[A-Z]{2,6}-\d{4}$/', Rule::unique('students', 'admission_no')->ignore($studentId)],
            'admission_date' => ['nullable', 'date', 'before_or_equal:today'],
            'dob' => ['nullable', 'date', 'before:today'],
            'national_id' => ['nullable', 'string', 'max:30', Rule::unique('students', 'national_id')->ignore($studentId)],
            'gender' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'guardian_id' => ['nullable', 'exists:guardians,id'],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => ['integer', 'distinct', 'exists:guardians,id'],
            'primary_guardian_id' => ['nullable', 'integer', 'exists:guardians,id'],
            'status' => ['required', 'in:active,suspended,graduated,withdrawn'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $guardianIds = collect($this->input('guardian_ids', []))->map(fn ($id) => (int) $id);
            $primaryGuardianId = $this->input('primary_guardian_id');

            if ($primaryGuardianId && ! $guardianIds->contains((int) $primaryGuardianId)) {
                $validator->errors()->add('primary_guardian_id', 'The primary guardian must also be selected as a guardian.');
            }

            if ($guardianIds->isEmpty() && ! $this->filled('guardian_id')) {
                $validator->errors()->add('guardian_ids', 'A student must have at least one guardian.');
            }
        });
    }

    public function passedValidation(): void
    {
        $student = $this->route('student');
        $studentId = $student instanceof Student ? $student->id : (int) $student;
        $classId = (int) $this->input('class_id');

        $schoolClass = SchoolClass::findOrFail($classId);

        $currentEnrollment = Student::where('class_id', $classId)
            ->where('id', '!=', $studentId)
            ->count();

        if ($currentEnrollment >= $schoolClass->capacity) {
            throw ValidationException::withMessages([
                'class_id' => "Class {$schoolClass->full_name} has reached maximum capacity ({$schoolClass->capacity} students).",
            ]);
        }
    }
}
