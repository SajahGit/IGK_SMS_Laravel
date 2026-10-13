<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\SubjectResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One place that reads attendance and results of ONE student.
 * Used by the Student portal, the Parent portal and the Report Card.
 */
class StudentReportService
{
    /**
     * @return array{rows: Collection, overall: array{total:int, attended:int, percentage:float|int}}
     * "attended" = present + late
     */
    public function attendanceSummary(Student $student): array
    {
        $rows = AttendanceRecord::query()
            ->join('attendance_sessions as s', 's.id', '=', 'attendance_records.attendance_session_id')
            ->join('class_subjects as cs', 'cs.id', '=', 's.class_subject_id')
            ->join('subjects as sub', 'sub.id', '=', 'cs.subject_id')
            ->where('attendance_records.student_id', $student->id)
            ->groupBy('sub.id', 'sub.name')
            ->orderBy('sub.name')
            ->select(
                'sub.name as subject',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'excused' THEN 1 ELSE 0 END) as excused")
            )
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->total > 0 ? round(($row->present + $row->late) / $row->total * 100, 1) : 0;

                return $row;
            });

        $total = (int) $rows->sum('total');
        $attended = (int) ($rows->sum('present') + $rows->sum('late'));

        return [
            'rows' => $rows,
            'overall' => [
                'total' => $total,
                'attended' => $attended,
                'percentage' => $total > 0 ? round($attended / $total * 100, 1) : 0,
            ],
        ];
    }

    /** Every attendance line, newest first. Each item has ->session_date, ->subject, ->status, ->remarks */
    public function attendanceList(Student $student, int $perPage = 15): LengthAwarePaginator
    {
        return AttendanceRecord::query()
            ->join('attendance_sessions as s', 's.id', '=', 'attendance_records.attendance_session_id')
            ->join('class_subjects as cs', 'cs.id', '=', 's.class_subject_id')
            ->join('subjects as sub', 'sub.id', '=', 'cs.subject_id')
            ->where('attendance_records.student_id', $student->id)
            ->orderByDesc('s.session_date')
            ->orderBy('sub.name')
            ->select('s.session_date', 'sub.name as subject', 'attendance_records.status', 'attendance_records.remarks')
            ->paginate($perPage, ['*'], 'att_page')
            ->withQueryString();
    }

    /**
     * Published results only (rows exist only after the teacher publishes the exam).
     * @return Collection<int, array{exam: \App\Models\Exam, result: ExamResult, subjects: Collection}>
     */
    public function results(Student $student, ?int $examId = null): Collection
    {
        return ExamResult::with('exam.term.academicYear')
            ->where('student_id', $student->id)
            ->when($examId, fn ($q) => $q->where('exam_id', $examId))
            ->orderByDesc('exam_id')
            ->get()
            ->map(function (ExamResult $result) use ($student) {
                $subjects = SubjectResult::with('classSubject.subject')
                    ->where('exam_id', $result->exam_id)
                    ->where('student_id', $student->id)
                    ->get()
                    ->sortBy(fn ($r) => $r->classSubject?->subject?->name)
                    ->values();

                return ['exam' => $result->exam, 'result' => $result, 'subjects' => $subjects];
            });
    }
}
