<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class StudentReportController extends Controller
{
    public function index(Request $request)
    {
        [$classes, $classId, $students] = $this->data($request);

        return view('reports.ReportStudents', compact('classes', 'classId', 'students'));
    }

    public function csv(Request $request)
    {
        [, , $students] = $this->data($request);

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Name', 'Email', 'Class', 'Parent', 'Phone']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->admission_no, $s->user->name, $s->user->email,
                    $s->class->full_name, $s->guardian?->user?->name, $s->phone,
                ]);
            }
            fclose($out);
        }, 'student-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        $classes = SchoolHelper::allowedClasses($request->user());
        $classId = $request->query('class_id');

        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot view this class.');
        }

        $students = Student::with(['user', 'class', 'guardian.user'])
            ->whereIn('class_id', $classId ? [(int) $classId] : $classes->pluck('id'))
            ->orderBy('class_id')->orderBy('admission_no')
            ->get();

        return [$classes, $classId, $students];
    }
}
