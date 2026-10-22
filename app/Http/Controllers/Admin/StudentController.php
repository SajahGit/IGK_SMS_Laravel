<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with(['user', 'class', 'guardian.user'])
            ->when($request->q, function ($query, $search) {
                $query->where(function ($w) use ($search) {
                    $w->where('admission_no', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->class_id, fn ($query, $classId) => $query->where('class_id', $classId))
            ->orderBy('admission_no')
            ->paginate(10)
            ->withQueryString();

        return view('admin.AdminStudentsIndex', [
            'students' => $students,
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.AdminStudentsCreate', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'class_id' => 'required|exists:classes,id',
            'admission_no' => 'required|max:30|unique:students,admission_no',
            'dob' => 'nullable|date|before:today',
            'phone' => 'nullable|max:20',
            'guardian_id' => 'nullable|exists:guardians,id',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'student',
            ]);

            Student::create([
                'user_id' => $user->id,
                'class_id' => $data['class_id'],
                'guardian_id' => $data['guardian_id'] ?? null,
                'admission_no' => $data['admission_no'],
                'dob' => $data['dob'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function edit(Student $student)
    {
        return view('admin.AdminStudentsEdit', $this->formData() + ['student' => $student->load('user')]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student->user_id)],
            'password' => 'nullable|min:8',
            'class_id' => 'required|exists:classes,id',
            'admission_no' => ['required', 'max:30', Rule::unique('students', 'admission_no')->ignore($student->id)],
            'dob' => 'nullable|date|before:today',
            'phone' => 'nullable|max:20',
            'guardian_id' => 'nullable|exists:guardians,id',
        ]);

        DB::transaction(function () use ($data, $student) {
            $user = $student->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            $student->update([
                'class_id' => $data['class_id'],
                'guardian_id' => $data['guardian_id'] ?? null,
                'admission_no' => $data['admission_no'],
                'dob' => $data['dob'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $student->user->delete(); // students row, attendance and results are removed by foreign key cascade

        return redirect()->route('admin.students.index')->with('success', 'Student deleted.');
    }

    private function formData(): array
    {
        return [
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'guardians' => Guardian::with('user')->get()->sortBy(fn ($g) => $g->user->name),
        ];
    }
}
