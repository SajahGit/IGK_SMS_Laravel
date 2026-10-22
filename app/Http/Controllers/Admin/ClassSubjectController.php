<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
{
    public function index()
    {
        return view('admin.AdminClassSubjectsIndex', [
            'assignments' => ClassSubject::with(['schoolClass', 'subject', 'teacher.user'])
                ->orderBy('class_id')->orderBy('subject_id')->get(),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->get()->sortBy(fn ($t) => $t->user->name),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => ['required', 'exists:subjects,id',
                Rule::unique('class_subject')->where('class_id', $request->class_id)],
            'teacher_id' => 'required|exists:teachers,id',
        ], ['subject_id.unique' => 'This subject is already assigned to that class.']);

        ClassSubject::create($data);

        return back()->with('success', 'Subject assigned to class.');
    }

    public function destroy(ClassSubject $classSubject)
    {
        $classSubject->delete();

        return back()->with('success', 'Assignment removed.');
    }
}
