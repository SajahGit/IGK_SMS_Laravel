<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $students, $filters, $programs, $academicYears] = $this->data($request);

        return view('Reports.ReportStudents', compact('classes', 'students', 'filters', 'programs', 'academicYears'));
    }

    public function pdf(Request $request): View
    {
        $limit = (int) $request->attributes->get('pdf_export_limit', 500);
        [$classes, $students, $filters, $programs, $academicYears] = $this->data($request, $limit);

        return view('Reports.ReportStudents', compact('classes', 'students', 'filters', 'programs', 'academicYears'));
    }

    public function csv(Request $request)
    {
        [, $students] = $this->data($request);

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Admission No', 'Name', 'Program', 'Section', 'Status', 'Admission Date', 'Guardian Phone']);
            $number = 0;
            foreach ($students as $s) {
                $number++;
                fputcsv($out, [
                    $number,
                    $s->admission_no,
                    $s->user->name,
                    $s->program?->name ?? $s->class->program?->name ?? '',
                    $s->class?->full_name ?? 'Unassigned',
                    $s->status,
                    $s->admission_date?->format('Y-m-d'),
                    $s->primaryGuardian->first()?->phone ?? '',
                ]);
            }
            fclose($out);
        }, 'student-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request, ?int $limit = null): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.academic'), 403);
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,suspended,graduated,withdrawn'],
        ]);
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $filters['class_id'] ?? null;

        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }

        $studentQuery = Student::query()
            ->select(['id', 'user_id', 'class_id', 'program_id', 'admission_no', 'status', 'admission_date', 'gender'])
            ->with([
                'user:id,name',
                'class:id,name,section,program_id',
                'class.program:id,name',
                'program:id,name',
                'primaryGuardian:id,phone',
            ])
            ->whereIn('class_id', $classId ? [(int) $classId] : $classes->pluck('id'))
            ->when(isset($filters['program_id']), fn ($query) => $query->where(function ($programs) use ($filters) {
                $programs->where('program_id', $filters['program_id'])
                    ->orWhereHas('class', fn ($classes) => $classes->where('program_id', $filters['program_id']));
            }))
            ->when(isset($filters['academic_year_id']), fn ($query) => $query->where(function ($years) use ($filters) {
                $years->where('academic_year_id', $filters['academic_year_id'])
                    ->orWhereHas('class', fn ($classes) => $classes->where('academic_year_id', $filters['academic_year_id']));
            }))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['gender']), fn ($query) => $query->where('gender', $filters['gender']));

        $totalCount = $limit === null ? null : (clone $studentQuery)->count('students.id');
        $students = $studentQuery
            ->orderBy('class_id')
            ->orderBy('admission_no')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();

        return [$classes, $students, $filters, Program::orderBy('name')->get(), AcademicYear::orderByDesc('start_date')->get(), $totalCount];
    }
}
