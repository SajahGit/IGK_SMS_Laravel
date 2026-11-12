<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('library.create'));
    }

    public function rules(): array
    {
        return ['condition' => ['required', 'in:good,fair,poor']];
    }
}
