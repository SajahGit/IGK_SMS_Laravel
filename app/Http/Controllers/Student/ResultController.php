<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;
use App\Support\ReportCardData;
use App\Support\SchoolHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $results = Result::with(['exam.subject', 'exam.term'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('is_published', true))
            ->get()
            ->sortByDesc(fn ($r) => $r->exam->exam_date);

        $student->load(['program.gradeScale.items', 'class.program.gradeScale.items']);
        $gradeScale = $student->program?->gradeScale ?? $student->class?->program?->gradeScale;
        $termGpa = SchoolHelper::calculateTermGpa($results, $gradeScale);

        return view('Student.StudentResults', [
            'results' => $results,
            'average' => SchoolHelper::studentAverage($student->id),
            'termGpa' => $termGpa,
            'student' => $student->load(['user', 'class']),
        ]);
    }

    public function reportCard(Request $request): View
    {
        $student = $this->studentProfile();
        $data = ReportCardData::forStudent($student, $request->integer('term_id') ?: null);
        $data['downloadUrl'] = route('student.report-card.pdf', $request->query());

        return view('Student.StudentReportCard', $data);
    }

    public function downloadReportCard(Request $request): Response
    {
        $student = $this->studentProfile();

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfReportCard', ReportCardData::forStudent($student, $request->integer('term_id') ?: null))
            ->setPaper('a4')
            ->download('report-card-'.$student->id.'.pdf');
    }

    private function studentProfile(): Student
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return $student;
    }
}
