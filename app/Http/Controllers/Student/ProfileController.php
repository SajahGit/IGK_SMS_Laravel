<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentDocument;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show()
    {
        return view('Student.StudentProfile', [
            'student' => $this->student()->load(['user', 'class', 'guardian.user', 'documents']),
        ]);
    }

    public function edit()
    {
        return view('Student.StudentProfileEdit', ['student' => $this->student()->load('user')]);
    }

    public function downloadDocument(StudentDocument $document): StreamedResponse
    {
        $student = $this->student();
        abort_unless($document->student_id === $student->id, 403, 'Unauthorized access to this document.');

        if (! Storage::disk('local')->exists($document->path)) {
            abort(404, 'File not found on private storage.');
        }

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function update(Request $request)
    {
        $student = $this->student();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|max:20',
            'password' => ['nullable', Password::defaults(), 'confirmed'],
        ]);

        // Only these fields can be changed by the student (not class, admission no or dob).
        $user = $student->user;
        $user->name = $data['name'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $oldProfile = ['phone' => $student->phone];
        $student->update(['phone' => $data['phone'] ?? null]);
        AuditLogger::log('student_profile_updated', $student, $oldProfile, ['phone' => $student->phone]);

        return redirect()->route('student.profile')->with('success', 'Profile updated.');
    }

    private function student()
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return $student;
    }
}
