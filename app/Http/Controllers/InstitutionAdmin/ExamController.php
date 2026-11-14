<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\MarkController;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ResultPublicationService;
use App\Support\AuditLogger;
use App\Support\ReportCardData;
use App\Support\SchoolHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ExamController extends Controller
{
    /** Read-only results page for admins (teachers use teacher.marks.show). */
    public function results(Exam $exam): View
    {
        return view('Teacher.TeacherMarksShow', MarkController::summary($exam));
    }

    public function index(Request $request): View
    {
        $classId = $request->query('class_id');
        $termId = $request->query('term_id');

        $classes = SchoolHelper::allowedClasses($request->user());
        $terms = Term::orderBy('sequence')->get();

        $exams = Exam::with(['class', 'subject', 'term', 'creator'])
            ->withCount('results')
            ->whereIn('class_id', $classes->pluck('id'))
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->orderByDesc('exam_date')
            ->paginate(15)
            ->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminExamsIndex', compact('exams', 'classes', 'terms', 'classId', 'termId'));
    }

    public function create(Request $request): View
    {
        $classes = SchoolHelper::allowedClasses($request->user());
        $terms = Term::orderByDesc('start_date')->get();
        $subjects = Subject::orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminExamsForm', compact('classes', 'terms', 'subjects'))
            ->with('exam', null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedExam($request);
        $data['created_by'] = $request->user()->id;
        $exam = Exam::create($data);
        AuditLogger::log('created_exam', $exam, null, $exam->only(['name', 'class_id', 'subject_id', 'term_id', 'exam_date']));

        return redirect()->route('admin.exams.index')->with('success', 'Exam created successfully.');
    }

    public function edit(Request $request, Exam $exam): View
    {
        abort_if($exam->is_published, 403, 'Published exams cannot be edited.');
        $classes = SchoolHelper::allowedClasses($request->user());
        abort_unless($classes->contains('id', $exam->class_id), 403);
        $terms = Term::orderByDesc('start_date')->get();
        $subjects = Subject::orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminExamsForm', compact('classes', 'terms', 'subjects', 'exam'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->is_published, 403, 'Published exams cannot be edited.');
        $before = $exam->only(['name', 'class_id', 'subject_id', 'term_id', 'exam_date', 'max_marks', 'weight']);
        $data = $this->validatedExam($request);
        $exam->update($data);
        AuditLogger::log('updated_exam', $exam, $before, $exam->only(array_keys($before)));

        return redirect()->route('admin.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        abort_if($exam->is_published, 403, 'Published exams cannot be deleted.');
        $before = $exam->only(['name', 'class_id', 'subject_id', 'term_id']);
        $exam->delete();
        AuditLogger::log('deleted_exam', $exam, $before, null);

        return redirect()->route('admin.exams.index')->with('success', 'Exam archived successfully.');
    }

    private function validatedExam(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'type' => ['required', 'string', 'max:30', 'regex:/\A[A-Za-z\s-]+\z/'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'exam_date' => ['required', 'date'],
            'max_marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        abort_unless(
            SchoolHelper::allowedClasses($request->user())->contains('id', (int) $data['class_id']),
            403,
            'You cannot create or edit an exam for this class.'
        );
        if (! ClassSubject::where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->where(function ($query) use ($data) {
                $query->where('term_id', $data['term_id'])->orWhereNull('term_id');
            })->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => 'The selected subject is not offered for this class and term.',
            ]);
        }

        return $data;
    }

    public function publish(Exam $exam): RedirectResponse
    {
        ResultPublicationService::publish($exam);

        return back()->with('success', "Exam '{$exam->name}' results have been published successfully.");
    }

    public function unpublish(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        ResultPublicationService::unpublish($exam);
        AuditLogger::log('unpublished_exam_results', $exam, [
            'is_published' => true,
        ], [
            'is_published' => false,
            'reason' => $data['reason'],
        ]);

        return back()->with('success', "Exam '{$exam->name}' results have been unpublished. Marks are unlocked.");
    }

    public function studentReportCard(Request $request, Student $student): View
    {
        $data = ReportCardData::forStudent($student, $request->integer('term_id') ?: null);
        $data['downloadUrl'] = route('admin.students.report-card.pdf', array_merge($request->query(), ['student' => $student->id]));

        return view('Student.StudentReportCard', $data);
    }

    public function downloadStudentReportCard(Request $request, Student $student): Response
    {
        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfReportCard', ReportCardData::forStudent($student, $request->integer('term_id') ?: null))
            ->setPaper('a4')
            ->download('report-card-'.$student->id.'.pdf');
    }
}
