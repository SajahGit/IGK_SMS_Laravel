<?php

namespace App\Http\Requests\Accountant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (auth()->user()->isAdmin()) {
            return true;
        }

        return match ($this->method()) {
            'POST' => auth()->check() && auth()->user()->can('fee_structures.create'),
            'PUT', 'PATCH' => auth()->check() && auth()->user()->can('fee_structures.update'),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            // SOP §15.1 / US-F1: the billing basis is program + academic year. A
            // structure is either program-wide (program_id given, no class_id) or,
            // as before, tied to one class (class_id given) — one of the two is
            // required, same as the original class-only form.
            'class_id' => ['required_without:program_id', 'nullable', 'exists:classes,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'program_id' => ['required_without:class_id', 'nullable', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                // SOP §11: a fee name is unique within its class + term (legacy scope).
                Rule::unique('fee_structures', 'name')
                    ->where(fn ($query) => $query
                        ->where('class_id', $this->input('class_id'))
                        ->where('term_id', $this->input('term_id') ?: null))
                    ->ignore($this->currentFeeStructureId()),
                // SOP-exact: also unique within its program + academic year, for a
                // program-wide structure created directly (no class_id).
                Rule::unique('fee_structures', 'name')
                    ->where(fn ($query) => $query
                        ->whereNull('class_id')
                        ->where('program_id', $this->input('program_id'))
                        ->where('academic_year_id', $this->input('academic_year_id') ?: null))
                    ->ignore($this->currentFeeStructureId()),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(config('school.currencies', ['LKR']))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function currentFeeStructureId(): ?int
    {
        $param = $this->route('feeStructure') ?? $this->route('fee_structure');

        if ($param === null) {
            return null;
        }

        return is_object($param) ? (int) $param->getKey() : (int) $param;
    }
}
