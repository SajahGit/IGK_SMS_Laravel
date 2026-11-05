<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\TimetableSlotRequest;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\TimetableSlot;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $classes = SchoolClass::orderBy('name')->orderBy('section')->get();
        $selectedClassId = $request->query('class_id') ?: ($classes->first()?->id ?? null);

        $classSubjects = collect();
        $slots = collect();

        if ($selectedClassId) {
            $classSubjects = ClassSubject::with(['subject', 'teacher.user'])
                ->where('class_id', $selectedClassId)
                ->get();

            $slots = TimetableSlot::with(['classSubject.subject', 'classSubject.teacher.user'])
                ->whereHas('classSubject', fn ($q) => $q->where('class_id', $selectedClassId))
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week');
        }

        return view('InstitutionAdmin.InstitutionAdminTimetableIndex', compact('classes', 'selectedClassId', 'classSubjects', 'slots'));
    }

    public function store(TimetableSlotRequest $request): RedirectResponse
    {
        $slot = TimetableSlot::create($request->validated());
        AuditLogger::log('created', $slot, null, $slot->toArray());

        $classId = $slot->classSubject->class_id;

        return redirect()->route('admin.timetable.index', ['class_id' => $classId])
            ->with('success', 'Timetable slot scheduled successfully with zero clashes.');
    }

    public function destroy(TimetableSlot $timetableSlot): RedirectResponse
    {
        $classId = $timetableSlot->classSubject->class_id;
        AuditLogger::log('deleted', $timetableSlot, $timetableSlot->toArray(), null);
        $timetableSlot->delete();

        return redirect()->route('admin.timetable.index', ['class_id' => $classId])
            ->with('success', 'Timetable slot removed.');
    }
}
