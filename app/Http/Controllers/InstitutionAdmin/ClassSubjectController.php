<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
{
    public function index()
    {
        return view('InstitutionAdmin.InstitutionAdminClassSubjectsIndex', [
            'assignments' => ClassSubject::with(['schoolClass' => fn ($q) => $q->withoutGlobalScopes(), 'subject', 'teacher.user', 'term'])
                ->whereHas('schoolClass', fn ($q) => $q->withoutGlobalScopes())
                ->whereHas('subject')
                ->orderByDesc('updated_at')
                ->latest('id')->get(),
            'classes' => SchoolClass::withoutGlobalScopes()->orderBy('name')->orderBy('section')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->get()->sortBy(fn ($t) => $t->user->name),
            'terms' => Term::with('academicYear')->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'term_id' => 'required|exists:terms,id',
            'subject_id' => ['required', 'exists:subjects,id', Rule::unique('class_subject')
                ->where('class_id', $request->class_id)
                ->where('term_id', $request->term_id)],
            'teacher_id' => 'required|exists:teachers,id',
        ], ['subject_id.unique' => 'This subject is already assigned to that class.']);

        SchoolClass::withoutGlobalScopes()->findOrFail($data['class_id']);

        DB::transaction(function () use ($data) {
            $assignment = ClassSubject::create($data);
            $students = Student::withoutGlobalScopes()->where('class_id', $assignment->class_id)
                ->where('status', 'active')->get();

            foreach ($students as $student) {
                $student->classSubjects()->syncWithoutDetaching([
                    $assignment->id => [
                        'status' => 'enrolled',
                        'enrolled_at' => now(),
                        'created_by' => auth()->id(),
                    ],
                ]);
            }

            $teacher = Teacher::find($data['teacher_id']);
            if ($teacher) {
                $schoolClass = SchoolClass::withoutGlobalScopes()->find($data['class_id']);
                if ($schoolClass && ! $teacher->branch_id && $schoolClass->branch_id) {
                    $teacher->branch_id = $schoolClass->branch_id;
                    if ($teacher->user && ! $teacher->user->branch_id) {
                        $teacher->user->update(['branch_id' => $schoolClass->branch_id]);
                    }
                }
                $teacher->touch();
            }
        });

        return back()->with('success', 'Subject assigned to class.');
    }

    public function destroy(ClassSubject $classSubject)
    {
        $teacher = $classSubject->teacher;
        $classSubject->delete();
        if ($teacher) {
            $teacher->touch();
        }

        return back()->with('success', 'Assignment removed.');
    }
}
