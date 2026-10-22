<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::with(['user', 'classSubjects.schoolClass', 'classSubjects.subject'])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.AdminTeachersIndex', compact('teachers'));
    }

    public function create()
    {
        return view('admin.AdminTeachersCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone' => 'nullable|max:20',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'teacher',
            ]);
            Teacher::create(['user_id' => $user->id, 'phone' => $data['phone'] ?? null]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.AdminTeachersEdit', ['teacher' => $teacher->load('user')]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'password' => 'nullable|min:8',
            'phone' => 'nullable|max:20',
        ]);

        DB::transaction(function () use ($data, $teacher) {
            $user = $teacher->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();
            $teacher->update(['phone' => $data['phone'] ?? null]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->user->delete(); // also removes the teacher's class-subject assignments

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted.');
    }
}
