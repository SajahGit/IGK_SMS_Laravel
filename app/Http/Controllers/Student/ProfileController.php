<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show()
    {
        return view('student.StudentProfile', ['student' => $this->student()->load(['user', 'class', 'guardian.user'])]);
    }

    public function edit()
    {
        return view('student.StudentProfileEdit', ['student' => $this->student()->load('user')]);
    }

    public function update(Request $request)
    {
        $student = $this->student();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|max:20',
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Only these fields can be changed by the student (not class, admission no or dob).
        $user = $student->user;
        $user->name = $data['name'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $student->update(['phone' => $data['phone'] ?? null]);

        return redirect()->route('student.profile')->with('success', 'Profile updated.');
    }

    private function student()
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return $student;
    }
}
