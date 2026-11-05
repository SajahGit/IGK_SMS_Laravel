<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;
use App\Support\ReportCardData;
use App\Support\SchoolHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ChildResultController extends Controller
{
    public function show(Student $student): View
    {
        $this->authorizeChild($student);

        $results = Result::with(['exam.subject', 'exam.term'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('is_published', true))
            ->get()
            ->sortByDesc(fn ($r) => $r->exam->exam_date);

        $termGpa = SchoolHelper::calculateTermGpa($results);

        return view('Guardian.GuardianResults', [
            'student' => $student->load(['user', 'class']),
            'children' => auth()->user()->guardian->students()->with('user')->get(),
            'results' => $results,
            'average' => SchoolHelper::studentAverage($student->id),
            'termGpa' => $termGpa,
        ]);
    }

    public function reportCard(Request $request, Student $student): View
    {
        $this->authorizeChild($student);
        $data = ReportCardData::forStudent($student, $request->integer('term_id') ?: null);
        $data['downloadUrl'] = route('guardian.report-card.pdf', array_merge($request->query(), ['student' => $student->id]));

        return view('Student.StudentReportCard', $data);
    }

    public function downloadReportCard(Request $request, Student $student): Response
    {
        $this->authorizeChild($student);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfReportCard', ReportCardData::forStudent($student, $request->integer('term_id') ?: null))
            ->setPaper('a4')
            ->download('report-card-'.$student->id.'.pdf');
    }

    private function authorizeChild(Student $student): void
    {
        $guardian = auth()->user()->guardian;
        abort_unless(
            $guardian && (
                $student->guardian_id === $guardian->id
                || $student->guardians()->whereKey($guardian->id)->exists()
            ),
            403,
            'This student is not linked to your account.'
        );
    }
}
