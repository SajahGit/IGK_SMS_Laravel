<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->paginate(10);

        return view('admin.AdminClassesIndex', compact('classes'));
    }

    public function create()
    {
        return view('admin.AdminClassesCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'section' => ['required', 'string', 'max:5', Rule::unique('classes')->where('name', $request->name)],
        ], ['section.unique' => 'This class and section already exist.']);

        SchoolClass::create($data);

        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }

    public function edit(SchoolClass $schoolClass)
    {
        return view('admin.AdminClassesEdit', compact('schoolClass'));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'section' => ['required', 'string', 'max:5',
                Rule::unique('classes')->where('name', $request->name)->ignore($schoolClass->id)],
        ], ['section.unique' => 'This class and section already exist.']);

        $schoolClass->update($data);

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if ($schoolClass->students()->exists()) {
            return back()->with('error', 'Cannot delete a class that still has students. Move the students first.');
        }

        $schoolClass->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Class deleted.');
    }
}
