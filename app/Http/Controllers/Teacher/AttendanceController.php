<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function create(Request $request)
    {
        $teacher = auth()->user()->teacher;
        $classes = SchoolClass::whereIn('id', SchoolHelper::teacherClassIds($teacher))
            ->orderBy('name')->orderBy('section')->get();

        $classId = $request->query('class_id');
        $date = $request->query('date', today()->toDateString());
        $students = collect();
        $existing = collect();

        if ($classId) {
            SchoolHelper::authorizeTeacherClass($teacher, $classId);

            $students = Student::with('user')->where('class_id', $classId)->orderBy('admission_no')->get();
            $existing = Attendance::where('class_id', $classId)->where('date', $date)->pluck('status', 'student_id');
        }

        return view('teacher.TeacherAttendanceCreate', compact('classes', 'classId', 'date', 'students', 'existing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date|before_or_equal:today',
            'status' => 'required|array',
            'status.*' => 'in:present,absent,late',
        ]);

        SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $data['class_id']);

        $studentIds = Student::where('class_id', $data['class_id'])->pluck('id');

        foreach ($data['status'] as $studentId => $status) {
            if (! $studentIds->contains((int) $studentId)) {
                continue; // ignore students who are not in this class
            }

            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $data['date']],
                ['class_id' => $data['class_id'], 'status' => $status, 'marked_by' => auth()->id()]
            );
        }

        return redirect()
            ->route('teacher.attendance.create', ['class_id' => $data['class_id'], 'date' => $data['date']])
            ->with('success', 'Attendance saved.');
    }

    public function index(Request $request)
    {
        $teacher = auth()->user()->teacher;
        $classes = SchoolClass::whereIn('id', SchoolHelper::teacherClassIds($teacher))
            ->orderBy('name')->orderBy('section')->get();

        $classId = $request->query('class_id');
        $from = $request->query('from', today()->subDays(30)->toDateString());
        $to = $request->query('to', today()->toDateString());
        $byDate = collect();

        if ($classId) {
            SchoolHelper::authorizeTeacherClass($teacher, $classId);

            $byDate = Attendance::where('class_id', $classId)
                ->where('date', '>=', $from)->where('date', '<=', $to)
                ->selectRaw('date, status, COUNT(*) as total')
                ->groupBy('date', 'status')
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('date')
                ->map(fn ($rows) => $rows->pluck('total', 'status'));
        }

        return view('teacher.TeacherAttendanceIndex', compact('classes', 'classId', 'from', 'to', 'byDate'));
    }
}
