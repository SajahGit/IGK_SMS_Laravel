<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('students.create'));
    }

    public function rules(): array
    {
        return [
            'existing_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['student', 'Student'])),
            ],
            'name' => ['required_without:existing_user_id', 'nullable', 'string', 'max:100'],
            'email' => ['required_without:existing_user_id', 'nullable', 'string', 'email', 'unique:users,email'],
            'password' => ['required_without:existing_user_id', 'nullable', 'string', Password::defaults()],
            'class_id' => ['required', 'exists:classes,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'admission_no' => ['nullable', 'string', 'regex:/^\d{4}-[A-Z]{2,6}-\d{4}$/', 'unique:students,admission_no'],
            'admission_date' => ['nullable', 'date', 'before_or_equal:today'],
            'dob' => ['nullable', 'date', 'before:today'],
            'national_id' => ['nullable', 'string', 'max:30', 'unique:students,national_id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'guardian_id' => ['nullable', 'exists:guardians,id'],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => ['integer', 'distinct', 'exists:guardians,id'],
            'primary_guardian_id' => ['nullable', 'integer', 'exists:guardians,id'],
            'status' => ['nullable', 'in:active,suspended,graduated,withdrawn'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Enforce Section Capacity constraint.
     */
    public function passedValidation(): void
    {
        $classId = (int) $this->input('class_id');
        $schoolClass = SchoolClass::findOrFail($classId);

        $currentEnrollment = Student::where('class_id', $classId)->count();

        if ($currentEnrollment >= $schoolClass->capacity) {
            throw ValidationException::withMessages([
                'class_id' => "Class {$schoolClass->full_name} has reached maximum capacity ({$schoolClass->capacity} students).",
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $guardianIds = collect($this->input('guardian_ids', []))->map(fn ($id) => (int) $id);
            $primaryGuardianId = $this->input('primary_guardian_id');

            if ($guardianIds->isEmpty() && ! $this->filled('guardian_id')) {
                $validator->errors()->add('guardian_ids', 'Select at least one guardian before admitting a student.');
            }

            if ($primaryGuardianId && ! $guardianIds->contains((int) $primaryGuardianId)) {
                $validator->errors()->add('primary_guardian_id', 'The primary guardian must also be selected as a guardian.');
            }

            $studentName = $this->input('name')
                ?? User::find($this->input('existing_user_id'))?->name;
            if ($studentName && $this->filled('dob')) {
                $duplicates = Student::with(['user', 'class'])
                    ->whereDate('dob', $this->input('dob'))
                    ->whereHas('user', fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($studentName))]))
                    ->get(['id', 'user_id', 'class_id', 'admission_no', 'dob']);

                if ($duplicates->isNotEmpty() && ! $this->boolean('confirm_duplicate')) {
                    $this->session()->flash('possible_duplicates', $duplicates->map(fn (Student $student) => [
                        'name' => $student->user->name,
                        'admission_no' => $student->admission_no,
                        'class' => $student->class?->full_name ?? '—',
                        'dob' => $student->dob?->format('Y-m-d'),
                    ])->all());
                    $validator->errors()->add('duplicate_confirmation', 'A student with the same name and date of birth may already exist. Review the matches and confirm only if this is a different student.');
                }
            }

            if (! $this->filled('class_id')) {
                return;
            }

            $schoolClass = SchoolClass::with(['program', 'academicYear'])->find($this->input('class_id'));
            if (! $schoolClass) {
                return;
            }

            $programId = $this->input('program_id') ?: $schoolClass->program_id;
            $academicYearId = $this->input('academic_year_id') ?: $schoolClass->academic_year_id;
            if (! $programId) {
                $validator->errors()->add('program_id', 'Assign a program to the class before admitting students.');
            } elseif ($schoolClass->program_id && (int) $programId !== (int) $schoolClass->program_id) {
                $validator->errors()->add('program_id', 'The selected program must match the class program.');
            }

            if (! $academicYearId) {
                $validator->errors()->add('academic_year_id', 'Assign an academic year to the class before admitting students.');
            } elseif ($schoolClass->academic_year_id && (int) $academicYearId !== (int) $schoolClass->academic_year_id) {
                $validator->errors()->add('academic_year_id', 'The selected academic year must match the class academic year.');
            }
        });
    }
}
