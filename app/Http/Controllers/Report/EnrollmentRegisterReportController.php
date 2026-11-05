<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Term;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentRegisterReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.academic'), 403);

        return view('Reports.ReportEnrollmentRegister', $this->reportData($request));
    }

    public function csv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('reports.academic'), 403);
        $rows = $this->reportData($request)['rows'];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Term', 'Program', 'Section', 'Subject', 'Teacher', 'Student Count', 'Student List']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['term'],
                    $row['program'],
                    $row['class'],
                    $row['subject'],
                    $row['teacher'],
                    $row['student_count'],
                    $row['student_list'],
                ]);
            }
            fclose($out);
        }, 'enrollment-register.csv', ['Content-Type' => 'text/csv']);
    }

    private function reportData(Request $request): array
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'status' => ['nullable', 'in:enrolled,dropped,completed'],
        ]);
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $filters['class_id'] ?? null;
        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }

        $classSubjects = ClassSubject::with(['subject', 'teacher.user', 'schoolClass.program', 'term'])
            ->whereIn('class_id', $classes->pluck('id'))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when(isset($filters['program_id']), fn ($query) => $query->whereHas('schoolClass', fn ($classes) => $classes->where('program_id', $filters['program_id'])))
            ->when(isset($filters['term_id']), fn ($query) => $query->where('term_id', $filters['term_id']))
            ->when(isset($filters['subject_id']), fn ($query) => $query->where('subject_id', $filters['subject_id']))
            ->orderBy('class_id')->orderBy('term_id')->orderBy('subject_id')->get();

        $rows = $classSubjects->map(function (ClassSubject $classSubject) use ($filters) {
            $students = $classSubject->students()
                ->with('user')
                ->when(isset($filters['status']), fn ($query) => $query->whereIn(
                    'students.id',
                    DB::table('student_subject_enrollments')
                        ->select('student_id')
                        ->where('class_subject_id', $classSubject->id)
                        ->where('status', $filters['status'])
                ))
                ->orderBy('admission_no')->get();

            return [
                'term' => $classSubject->term?->name ?? 'Legacy offering',
                'program' => $classSubject->schoolClass->program?->name ?? '—',
                'subject' => $classSubject->subject->name.' ('.$classSubject->subject->code.')',
                'class' => $classSubject->schoolClass->full_name,
                'teacher' => $classSubject->teacher?->user?->name ?? '—',
                'student_count' => $students->count(),
                'student_list' => $students->map(fn ($student) => $student->admission_no.' - '.$student->user->name)->implode(', ') ?: '—',
            ];
        });

        return [
            'classes' => $classes,
            'subjects' => Subject::orderBy('name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'terms' => Term::with('academicYear')->orderByDesc('start_date')->get(),
            'filters' => $filters,
            'rows' => $rows,
        ];
    }
}
