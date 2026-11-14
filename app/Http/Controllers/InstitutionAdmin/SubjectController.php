<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')->paginate(10);

        return view('InstitutionAdmin.InstitutionAdminSubjectsIndex', compact('subjects'));
    }

    public function create()
    {
        return view('InstitutionAdmin.InstitutionAdminSubjectsCreate');
    }

    public function store(Request $request)
    {
        $code = $request->input('code');
        if (is_string($code)) {
            $request->merge(['code' => strtoupper($code)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'code' => ['required', 'string', 'max:15', 'regex:/\A[A-Z][A-Z0-9-]*\z/', 'unique:subjects,code'],
        ]);

        $subject = Subject::create($data);
        AuditLogger::log('created_subject', $subject, null, $subject->toArray());

        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        return view('InstitutionAdmin.InstitutionAdminSubjectsEdit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $code = $request->input('code');
        if (is_string($code)) {
            $request->merge(['code' => strtoupper($code)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'code' => ['required', 'string', 'max:15', 'regex:/\A[A-Z][A-Z0-9-]*\z/', Rule::unique('subjects', 'code')->ignore($subject->id)],
        ]);

        $before = $subject->toArray();
        $subject->update($data);
        AuditLogger::log('updated_subject', $subject, $before, $subject->toArray());

        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $before = $subject->toArray();
        $subject->delete();
        AuditLogger::log('deleted_subject', $subject, $before, null);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject deleted.');
    }
}
