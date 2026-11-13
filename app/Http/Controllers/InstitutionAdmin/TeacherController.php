<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::with([
            'user',
            'department',
            'classSubjects' => fn ($query) => $query->whereHas('schoolClass')->whereHas('subject'),
            'classSubjects.schoolClass',
            'classSubjects.subject',
        ])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminTeachersIndex', compact('teachers'));
    }

    public function create()
    {
        return view('InstitutionAdmin.InstitutionAdminTeachersCreate', ['departments' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('teachers', 'phone'), new ValidInternationalPhone($request->input('phone_region'))],
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'teacher',
                'must_change_password' => true,
            ]);
            Teacher::create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'status' => $data['status'],
            ]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher)
    {
        return view('InstitutionAdmin.InstitutionAdminTeachersEdit', [
            'teacher' => $teacher->load('user'),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('teachers', 'phone')->ignore($teacher), new ValidInternationalPhone($request->input('phone_region'))],
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        DB::transaction(function () use ($data, $teacher) {
            $user = $teacher->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->must_change_password = true;
            }
            $user->save();
            $teacher->update([
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'status' => $data['status'],
            ]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->user->delete(); // also removes the teacher's class-subject assignments

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted.');
    }
}
