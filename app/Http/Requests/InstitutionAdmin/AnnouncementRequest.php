<?php

namespace App\Http\Requests\InstitutionAdmin;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('put') || $this->isMethod('patch')
            ? 'announcements.update'
            : 'announcements.create';

        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can($permission));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-:()!]*\z/'],
            'body' => ['required', 'string'],
            'target_role' => ['required', 'string', 'in:all,admin,registrar,teacher,accountant,student,parent'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'published_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
