<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->paginate(10);

        return view('InstitutionAdmin.InstitutionAdminClassesIndex', compact('classes'));
    }

    public function create()
    {
        return view('InstitutionAdmin.InstitutionAdminClassesCreate', $this->formData());
    }

    public function store(Request $request)
    {
        $section = $request->input('section');
        if (is_string($section)) {
            $request->merge(['section' => strtoupper($section)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/\A[A-Za-z][A-Za-z0-9\s-]*\z/'],
            'section' => ['required', 'string', 'max:10', 'regex:/\A[A-Z0-9]+\z/', Rule::unique('classes')->where('name', $request->name)],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'branch_id' => ['nullable', Rule::requiredIf(Branch::where('is_active', true)->exists()), 'exists:branches,id'],
        ], ['section.unique' => 'This class and section already exist.']);

        $schoolClass = SchoolClass::create($data);
        AuditLogger::log('created_class', $schoolClass, null, $schoolClass->toArray());

        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }

    public function edit(SchoolClass $schoolClass)
    {
        return view('InstitutionAdmin.InstitutionAdminClassesEdit', ['schoolClass' => $schoolClass] + $this->formData());
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $section = $request->input('section');
        if (is_string($section)) {
            $request->merge(['section' => strtoupper($section)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/\A[A-Za-z][A-Za-z0-9\s-]*\z/'],
            'section' => ['required', 'string', 'max:10', 'regex:/\A[A-Z0-9]+\z/',
                Rule::unique('classes')->where('name', $request->name)->ignore($schoolClass->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'branch_id' => ['nullable', Rule::requiredIf(Branch::where('is_active', true)->exists()), 'exists:branches,id'],
        ], ['section.unique' => 'This class and section already exist.']);

        $before = $schoolClass->toArray();
        $schoolClass->update($data);
        AuditLogger::log('updated_class', $schoolClass, $before, $schoolClass->toArray());

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if ($schoolClass->students()->exists()) {
            return back()->with('error', 'Cannot delete a class that still has students. Move the students first.');
        }

        $before = $schoolClass->toArray();
        $schoolClass->delete();
        AuditLogger::log('deleted_class', $schoolClass, $before, null);

        return redirect()->route('admin.classes.index')->with('success', 'Class deleted.');
    }

    private function formData(): array
    {
        return [
            'programs' => Program::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
