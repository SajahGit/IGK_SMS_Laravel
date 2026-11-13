<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Rules\UniqueStudentDocumentNumber;
use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Rules\ValidStudentDocument;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        return view('Admin.AdminStudentsIndex', [
            'students' => $students,
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ]);
    }

    public function create()
    {
        return view('Admin.AdminStudentsCreate', $this->formData());
    }

    public function store(Request $request)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $this->normalizeDocumentNumber($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:32',
            'class_id' => 'required|exists:classes,id',
            'admission_no' => 'required|max:30|unique:students,admission_no',
            'dob' => 'nullable|date|before:today',
            'document_type' => ['required', Rule::in(['national_id', 'passport'])],
            'issuing_country' => ['required', 'string', 'size:2'],
            'document_number' => ['required', 'string', 'max:30', new ValidStudentDocument($request->input('document_type'), $request->input('issuing_country')), new UniqueStudentDocumentNumber],
            'nationality' => ['nullable', Rule::in(array_column(config('school.demo_nationalities', []), 'label'))],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('students', 'phone'), new ValidInternationalPhone($request->input('phone_region'))],
            'guardian_id' => 'nullable|exists:guardians,id',
        ]);
        $this->ensureClassHasCapacity((int) $data['class_id']);

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
                'document_type' => $data['document_type'],
                'issuing_country' => $data['issuing_country'],
                'document_number' => $data['document_number'],
                'nationality' => $data['nationality'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function edit(Student $student)
    {
        return view('Admin.AdminStudentsEdit', $this->formData() + ['student' => $student->load('user')]);
    }

    public function update(Request $request, Student $student)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $this->normalizeDocumentNumber($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student->user_id)],
            'password' => 'nullable|string|min:8|max:32',
            'class_id' => 'required|exists:classes,id',
            'admission_no' => ['required', 'max:30', Rule::unique('students', 'admission_no')->ignore($student->id)],
            'dob' => 'nullable|date|before:today',
            'document_type' => ['required', Rule::in(['national_id', 'passport'])],
            'issuing_country' => ['required', 'string', 'size:2'],
            'document_number' => ['required', 'string', 'max:30', new ValidStudentDocument($request->input('document_type'), $request->input('issuing_country')), new UniqueStudentDocumentNumber($student->id)],
            'nationality' => ['nullable', Rule::in(array_column(config('school.demo_nationalities', []), 'label'))],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('students', 'phone')->ignore($student), new ValidInternationalPhone($request->input('phone_region'))],
            'guardian_id' => 'nullable|exists:guardians,id',
        ]);
        $this->ensureClassHasCapacity((int) $data['class_id'], $student);

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
                'document_type' => $data['document_type'],
                'issuing_country' => $data['issuing_country'],
                'document_number' => $data['document_number'],
                'nationality' => $data['nationality'] ?? null,
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
            'classes' => SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->get()
                ->sortBy(fn (SchoolClass $schoolClass) => $schoolClass->students_count >= $schoolClass->capacity ? 1 : 0)
                ->values(),
            'guardians' => Guardian::with('user')->get()->sortBy(fn ($g) => $g->user->name),
        ];
    }

    private function normalizeDocumentNumber(Request $request): void
    {
        if (is_string($request->input('document_number'))) {
            $request->merge(['document_number' => Str::upper(trim($request->input('document_number')))]);
        }
        if (is_string($request->input('issuing_country'))) {
            $request->merge(['issuing_country' => Str::upper(trim($request->input('issuing_country')))]);
        }
    }

    private function ensureClassHasCapacity(int $classId, ?Student $student = null): void
    {
        $schoolClass = SchoolClass::findOrFail($classId);
        $enrollment = Student::query()
            ->where('class_id', $classId)
            ->when($student, fn ($query) => $query->where('id', '!=', $student->id))
            ->count();

        if ($enrollment >= $schoolClass->capacity) {
            throw ValidationException::withMessages([
                'class_id' => "Class {$schoolClass->full_name} has reached maximum capacity ({$schoolClass->capacity} students).",
            ]);
        }
    }
}
