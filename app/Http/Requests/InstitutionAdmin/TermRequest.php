<?php

namespace App\Http\Requests\InstitutionAdmin;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z][A-Za-z0-9\s-]*\z/'],
            'sequence' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['academic_year_id', 'start_date', 'end_date'])) {
                    return;
                }

                $academicYear = AcademicYear::find($this->integer('academic_year_id'));
                if (! $academicYear) {
                    return;
                }

                $startDate = \Illuminate\Support\Carbon::parse($this->input('start_date'));
                $endDate = \Illuminate\Support\Carbon::parse($this->input('end_date'));

                if ($startDate->lt($academicYear->start_date) || $startDate->gt($academicYear->end_date)) {
                    $validator->errors()->add('start_date', 'The term start date must fall within the selected academic year.');
                }

                if ($endDate->lt($academicYear->start_date) || $endDate->gt($academicYear->end_date)) {
                    $validator->errors()->add('end_date', 'The term end date must fall within the selected academic year.');
                }
            },
        ];
    }
}
