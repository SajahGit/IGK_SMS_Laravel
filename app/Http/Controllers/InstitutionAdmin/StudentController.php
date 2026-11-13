<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreStudentRequest;
use App\Http\Requests\InstitutionAdmin\UpdateStudentRequest;
use App\Http\Requests\InstitutionAdmin\UploadStudentDocumentRequest;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\SavedFilter;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = $this->studentQuery($request)
            ->with(['user', 'class', 'primaryGuardian.user'])
            ->withCount('documents')
            ->orderBy('admission_no')
            ->paginate(10)
            ->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminStudentsIndex', [
            'students' => $students,
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
            'savedFilters' => SavedFilter::where('user_id', $request->user()->id)
                ->where('scope', 'students')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $students = $this->studentQuery($request)
            ->with(['user', 'class', 'primaryGuardian.user'])
            ->orderBy('admission_no');

        return response()->streamDownload(function () use ($students) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Admission No', 'Student', 'Email', 'Class', 'Guardian', 'Guardian Phone', 'Student Phone', 'Date of Birth']);

            $students->chunkById(500, function ($batch) use ($output) {
                foreach ($batch as $student) {
                    fputcsv($output, array_map(fn ($value) => $this->safeCsvField($value), [
                        $student->admission_no,
                        $student->user->name,
                        $student->user->email,
                        $student->class?->full_name ?? 'Unassigned',
                        $student->primaryGuardian->first()?->user?->name,
                        $student->primaryGuardian->first()?->phone,
                        $student->phone,
                        $student->dob?->format('Y-m-d'),
                    ]));
                }
            });

            fclose($output);
        }, 'student-register.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        return view('InstitutionAdmin.InstitutionAdminStudentsCreate', $this->formData() + [
            'unlinkedStudentUsers' => User::whereIn('role', ['student', 'Student'])
                ->whereDoesntHave('student')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = EnrollmentService::enrollStudent($request->validated());

        return redirect()->route('admin.students.index')
            ->with('success', "Student {$student->user->name} enrolled successfully. Admission number: {$student->admission_no}.");
    }

    public function edit(Student $student): View
    {
        return view('InstitutionAdmin.InstitutionAdminStudentsEdit', $this->formData() + [
            'student' => $student->load([
                'user',
                'documents.uploader',
                'guardians',
                'primaryGuardian',
                'classSubjects.subject',
                'classSubjects.term',
                'classSubjects.schoolClass',
            ]),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $oldStudent = $student->toArray();
        $newClass = SchoolClass::findOrFail($data['class_id']);

        DB::transaction(function () use ($data, $student, $oldStudent, $newClass) {
            $user = $student->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->must_change_password = true;
            }
            $user->save();

            $student->update([
                'class_id' => $data['class_id'],
                'program_id' => $data['program_id'] ?? $newClass->program_id,
                'academic_year_id' => $data['academic_year_id'] ?? $newClass->academic_year_id,
                'admission_no' => $data['admission_no'],
                'admission_date' => $data['admission_date'] ?? $student->admission_date,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'document_type' => $data['document_type'],
                'issuing_country' => $data['issuing_country'],
                'document_number' => $data['document_number'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);
            EnrollmentService::syncGuardians($student, $data);
            if ((int) $oldStudent['class_id'] !== (int) $data['class_id']) {
                EnrollmentService::syncClassSubjects($student);
            }

            AuditLogger::log('updated', $student, $oldStudent, $student->toArray());
        });

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        EnrollmentService::dropStudent($student);

        return redirect()->route('admin.students.index')->with('success', 'Student dropped.');
    }

    public function dropClassSubjectEnrollment(Student $student, ClassSubject $classSubject): RedirectResponse
    {
        $enrollment = $student->classSubjects()
            ->where('class_subject.id', $classSubject->id)
            ->first();

        abort_unless($enrollment && $enrollment->pivot->status === 'enrolled', 404);

        DB::transaction(function () use ($student, $classSubject, $enrollment) {
            $student->classSubjects()->updateExistingPivot($classSubject->id, [
                'status' => 'dropped',
                'dropped_at' => now(),
            ]);
            AuditLogger::log('dropped_subject_enrollment', $student, [
                'class_subject_id' => $classSubject->id,
                'status' => $enrollment->pivot->status,
            ], [
                'class_subject_id' => $classSubject->id,
                'status' => 'dropped',
            ]);
        });

        return back()->with('success', 'Student dropped from the subject. Existing academic records are retained.');
    }

    /**
     * Upload student document (strictly stored on private local disk).
     */
    public function uploadDocument(UploadStudentDocumentRequest $request, Student $student): RedirectResponse
    {
        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();

        // Stored on private local disk in storage/app/private/student_documents
        $path = $file->store('student_documents', 'local');

        $doc = StudentDocument::create([
            'student_id' => $student->id,
            'type' => $request->input('type'),
            'original_name' => $originalName,
            'path' => $path,
            'mime' => $mime,
            'size' => $size,
            'uploaded_by' => auth()->id(),
        ]);

        AuditLogger::log('uploaded_document', $doc, null, ['name' => $originalName]);

        return back()->with('success', "Document '{$originalName}' uploaded successfully.");
    }

    /**
     * Download student document with authorization.
     */
    public function downloadDocument(StudentDocument $document): StreamedResponse
    {
        if (! Storage::disk('local')->exists($document->path)) {
            abort(404, 'Document file not found on private storage.');
        }

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    /**
     * Delete student document.
     */
    public function destroyDocument(StudentDocument $document): RedirectResponse
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();

        AuditLogger::log('deleted_document', $document);

        return back()->with('success', 'Document deleted.');
    }

    private function formData(): array
    {
        return [
            'classes' => SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->get()
                ->sortBy(fn (SchoolClass $schoolClass) => $schoolClass->students_count >= $schoolClass->capacity ? 1 : 0)
                ->values(),
            'guardians' => Guardian::with('user')->get()->sortBy(fn ($g) => $g->user->name),
            'programs' => Program::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ];
    }

    private function studentQuery(Request $request)
    {
        return Student::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($studentQuery) use ($search) {
                    $studentQuery->where('admission_no', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')));
    }

    private function safeCsvField(?string $value): ?string
    {
        return $value !== null && preg_match('/^[\t\r\n ]*[=+\-@]/', $value)
            ? "'".$value
            : $value;
    }
}
