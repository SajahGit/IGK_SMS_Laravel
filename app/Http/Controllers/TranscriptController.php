<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\TranscriptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TranscriptController extends Controller
{
    public function student(): View
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return view('Student.StudentTranscript', TranscriptService::forStudent($student));
    }

    public function studentPdf(): Response
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfTranscript', TranscriptService::forStudent($student))
            ->setPaper('a4')
            ->download('transcript-'.$student->admission_no.'.pdf');
    }

    public function guardian(Student $student): View
    {
        $this->authorizeChild($student);

        return view('Student.StudentTranscript', TranscriptService::forStudent($student));
    }

    public function guardianPdf(Student $student): Response
    {
        $this->authorizeChild($student);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfTranscript', TranscriptService::forStudent($student))
            ->setPaper('a4')
            ->download('transcript-'.$student->admission_no.'.pdf');
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
