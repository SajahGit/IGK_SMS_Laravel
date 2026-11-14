<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\Term;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceSummaryPerStudentReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.attendance'), 403);

        [$classes, $classId, $from, $to, $studentId, $termId, $subjectId, $terms, $subjects, $students, $rows] = $this->data($request);

        return view('Reports.ReportAttendanceSummaryPerStudent', compact(
            'classes', 'classId', 'from', 'to', 'studentId', 'termId', 'subjectId', 'terms', 'subjects', 'students', 'rows'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('reports.attendance'), 403);

        [, , $from, $to, , $termId, $subjectId, , , , $rows] = $this->data($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Student', 'Sessions Held', 'Present', 'Absent', 'Late', 'Excused', 'Attendance %']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['admission_no'], $row['name'], $row['sessions_held'], $row['present'],
                    $row['absent'], $row['late'], $row['excused'], $row['percentage'].'%',
                ]);
            }
            fclose($out);
        }, "attendance-summary-term-{$termId}-subject-{$subjectId}.csv", ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.attendance'), 403);
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'subject_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $validated['class_id'] ?? null;
        $from = $validated['from'] ?? today()->subDays(30)->toDateString();
        $to = $validated['to'] ?? today()->toDateString();
        $studentId = $validated['student_id'] ?? null;
        $termId = isset($validated['term_id']) ? (int) $validated['term_id'] : null;
        $subjectId = isset($validated['subject_id']) ? (int) $validated['subject_id'] : null;
        $pdfLimit = $request->attributes->get('pdf_export_limit');
        $students = collect();
        $rows = collect();
        $subjects = collect();

        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
            $subjects = ClassSubject::with('subject')
                ->where('class_id', $classId)
                ->get()
                ->pluck('subject')
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values();
            $studentQuery = Student::query()
                ->select(['id', 'user_id', 'class_id', 'admission_no'])
                ->with('user:id,name')
                ->where('class_id', $classId);
            if ($studentId) {
                $studentQuery->whereKey($studentId);
            }
            $students = $studentQuery
                ->orderBy('admission_no')
                ->when(is_int($pdfLimit), fn ($query) => $query->limit($pdfLimit))
                ->get();
            $rows = $students->map(function ($student) use ($from, $to, $termId, $subjectId) {
                $summary = SchoolHelper::attendanceSummary($student->id, $from, $to, $termId, $subjectId);

                return [
                    'student' => $student,
                    'admission_no' => $student->admission_no,
                    'name' => $student->user->name,
                    'sessions_held' => $summary['total'],
                    'present' => $summary['present'],
                    'absent' => $summary['absent'],
                    'late' => $summary['late'],
                    'excused' => $summary['excused'],
                    'percentage' => $summary['percentage'],
                ];
            });
        }

        return [
            $classes, $classId, $from, $to, $studentId, $termId, $subjectId,
            Term::orderByDesc('start_date')->get(), $subjects, $students, $rows,
        ];
    }
}
