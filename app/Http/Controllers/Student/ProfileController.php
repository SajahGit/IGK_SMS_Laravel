<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

class ProfileController extends StudentBaseController
{
    public function show()
    {
        $student = $this->me()->load(['guardians', 'user']);

        return view('Student.Profile.Show', compact('student'));
    }

    /** Students may change only these safe fields. */
    public function update(Request $request)
    {
        $student = $this->me();

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $student->update(['address' => $data['address'] ?? null]);
        $student->user->update(['phone' => $data['phone'] ?? null]);

        return back()->with('success', 'Profile updated.');
    }
}
