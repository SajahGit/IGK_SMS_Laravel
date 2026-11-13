<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Hostel;
use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Services\HostelAssignmentService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HostelController extends Controller
{
    public function index(): View
    {
        $hostels = Hostel::query()
            ->with(['rooms' => fn ($query) => $query->withCount([
                'assignments as active_assignments' => fn ($assignments) => $assignments
                    ->where('status', 'active')
                    ->where(fn ($active) => $active->whereNull('end_date')->orWhereDate('end_date', '>=', today())),
            ])])
            ->orderBy('name')
            ->get();
        $assignments = HostelAssignment::query()
            ->with(['student.user', 'room.hostel', 'academicYear'])
            ->whereHas('student')
            ->whereHas('room.hostel')
            ->whereHas('academicYear')
            ->latest('start_date')
            ->paginate(25)
            ->withQueryString();

        return view('InstitutionAdmin.HostelIndex', compact('hostels', 'assignments'));
    }

    public function storeHostel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', "regex:/^[a-zA-Z0-9\\s\\-']+$/", 'unique:hostels,name'],
            'location' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s,.-]+$/'],
        ]);
        $hostel = Hostel::create($data + ['is_active' => true]);
        AuditLogger::log('created', $hostel, null, $hostel->toArray());

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel created.');
    }

    public function updateHostel(Request $request, Hostel $hostel): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', "regex:/^[a-zA-Z0-9\\s\\-']+$/", Rule::unique('hostels', 'name')->ignore($hostel->id)],
            'location' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s,.-]+$/'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($hostel, $data): void {
            $old = $hostel->toArray();
            $hostel->update($data);
            if (! $hostel->is_active) {
                $hostel->rooms()->update(['is_active' => false]);
            }
            AuditLogger::log('updated', $hostel, $old, $hostel->fresh()->toArray());
        });

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel updated.');
    }

    public function destroyHostel(Hostel $hostel): RedirectResponse
    {
        if ($hostel->rooms()->exists()) {
            return back()->withErrors(['hostel' => 'A hostel with rooms or assignment history cannot be deleted. Deactivate it instead.']);
        }

        $before = $hostel->toArray();
        $hostel->delete();
        AuditLogger::log('deleted', $hostel, $before, null);

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel deleted.');
    }

    public function storeRoom(Request $request, Hostel $hostel): RedirectResponse
    {
        if (is_string($request->input('room_number'))) {
            $request->merge(['room_number' => strtoupper(trim($request->input('room_number')))]);
        }
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:15', 'regex:/^[A-Z0-9-]+$/', Rule::unique('hostel_rooms')->where('hostel_id', $hostel->id)],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        abort_unless($hostel->is_active, 422, 'Rooms can only be added to an active hostel.');
        $room = $hostel->rooms()->create($data + ['is_active' => true]);
        AuditLogger::log('created', $room, null, $room->toArray());

        return redirect()->route('admin.hostels.index')->with('success', 'Room created.');
    }

    public function updateRoom(Request $request, HostelRoom $room): RedirectResponse
    {
        if (is_string($request->input('room_number'))) {
            $request->merge(['room_number' => strtoupper(trim($request->input('room_number')))]);
        }
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:15', 'regex:/^[A-Z0-9-]+$/', Rule::unique('hostel_rooms')->where('hostel_id', $room->hostel_id)->ignore($room->id)],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($data['is_active'] && ! $room->hostel()->where('is_active', true)->exists()) {
            return back()->withErrors(['is_active' => 'A room cannot be activated while its hostel is inactive.']);
        }
        $highestAssignedBed = $room->assignments()->max('bed_number') ?? 0;
        if ($data['bed_capacity'] < $highestAssignedBed) {
            return back()->withErrors(['bed_capacity' => 'Capacity cannot be below a bed number in the assignment history.']);
        }

        DB::transaction(function () use ($room, $data): void {
            $old = $room->toArray();
            $room->update($data);
            AuditLogger::log('updated', $room, $old, $room->fresh()->toArray());
        });

        return redirect()->route('admin.hostels.index')->with('success', 'Room updated.');
    }

    public function destroyRoom(HostelRoom $room): RedirectResponse
    {
        if ($room->assignments()->exists()) {
            return back()->withErrors(['room' => 'A room with assignment history cannot be deleted. Deactivate it instead.']);
        }

        $before = $room->toArray();
        $room->delete();
        AuditLogger::log('deleted', $room, $before, null);

        return redirect()->route('admin.hostels.index')->with('success', 'Room deleted.');
    }

    public function createAssignment(): View
    {
        $assignment = null;
        $students = Student::query()->with('user')->where('status', 'active')->whereHas('user')->orderBy('admission_no')->get();
        $rooms = HostelRoom::query()->where('is_active', true)->whereHas('hostel', fn ($query) => $query->where('is_active', true))
            ->with('hostel')->orderBy('room_number')->get();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        return view('InstitutionAdmin.HostelAssignmentForm', compact('assignment', 'students', 'rooms', 'academicYears'));
    }

    public function editAssignment(HostelAssignment $assignment): View
    {
        $students = Student::query()->with('user')->where('status', 'active')->whereHas('user')
            ->orWhere('id', $assignment->student_id)->orderBy('admission_no')->get();
        $rooms = HostelRoom::query()
            ->where('is_active', true)
            ->orWhere('id', $assignment->hostel_room_id)
            ->with('hostel')
            ->orderBy('room_number')
            ->get();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        return view('InstitutionAdmin.HostelAssignmentForm', compact('assignment', 'students', 'rooms', 'academicYears'));
    }

    public function storeAssignment(Request $request, HostelAssignmentService $service): RedirectResponse
    {
        $service->assign(null, $this->validatedAssignment($request));

        return redirect()->route('admin.hostels.index')->with('success', 'Student hostel assignment created.');
    }

    public function updateAssignment(Request $request, HostelAssignment $assignment, HostelAssignmentService $service): RedirectResponse
    {
        $service->assign($assignment, $this->validatedAssignment($request, $assignment));

        return redirect()->route('admin.hostels.index')->with('success', 'Student hostel assignment updated.');
    }

    public function endAssignment(HostelAssignment $assignment, HostelAssignmentService $service): RedirectResponse
    {
        $endDate = max($assignment->start_date->toDateString(), today()->toDateString());
        $service->end($assignment, $endDate);

        return redirect()->route('admin.hostels.index')->with('success', 'Student hostel assignment ended.');
    }

    private function validatedAssignment(Request $request, ?HostelAssignment $assignment = null): array
    {
        return $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('status', 'active')],
            'hostel_room_id' => ['required', 'integer', 'exists:hostel_rooms,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'bed_number' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => [$assignment ? 'required' : 'sometimes', Rule::in(['active', 'inactive'])],
        ]);
    }
}
