<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\StoreStudentTransportAssignmentRequest;
use App\Http\Requests\Transport\StoreTransportRouteRequest;
use App\Http\Requests\Transport\StoreTransportStopRequest;
use App\Http\Requests\Transport\StoreTransportVehicleRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportVehicle;
use App\Services\TransportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransportController extends Controller
{
    public function index(): View
    {
        $routes = TransportRoute::query()
            ->with(['vehicle', 'stops'])
            ->withCount(['assignments as active_assignments' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();
        $vehicles = TransportVehicle::query()->orderBy('registration_number')->get();
        $assignments = StudentTransportAssignment::query()
            ->with(['student.user', 'route', 'stop', 'academicYear'])
            ->whereHas('student')
            ->whereHas('route')
            ->whereHas('stop')
            ->whereHas('academicYear')
            ->latest('start_date')
            ->paginate(25, ['*'], 'assignments_page')
            ->withQueryString();

        return view('InstitutionAdmin.TransportIndex', compact('routes', 'vehicles', 'assignments'));
    }

    public function routeCreate(): View
    {
        $transportRoute = null;
        $vehicles = TransportVehicle::query()->where('status', 'active')->orderBy('registration_number')->get();

        return view('InstitutionAdmin.TransportRouteForm', compact('transportRoute', 'vehicles'));
    }

    public function routeStore(StoreTransportRouteRequest $request): RedirectResponse
    {
        $route = TransportService::saveRoute(null, $request->validated() + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.transport.routes.edit', $route)->with('success', 'Transport route created.');
    }

    public function routeEdit(TransportRoute $transportRoute): View
    {
        $transportRoute->load(['vehicle', 'stops.assignments']);
        $vehicles = TransportVehicle::query()->where('status', 'active')
            ->when($transportRoute->vehicle_id, fn ($query, $id) => $query->orWhere('id', $id))
            ->orderBy('registration_number')
            ->get();

        return view('InstitutionAdmin.TransportRouteForm', compact('transportRoute', 'vehicles'));
    }

    public function routeUpdate(StoreTransportRouteRequest $request, TransportRoute $transportRoute): RedirectResponse
    {
        TransportService::saveRoute($transportRoute, $request->validated() + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.transport.routes.edit', $transportRoute)->with('success', 'Transport route updated.');
    }

    public function routeDestroy(TransportRoute $transportRoute): RedirectResponse
    {
        TransportService::deleteRoute($transportRoute);

        return redirect()->route('admin.transport.index')->with('success', 'Transport route deleted.');
    }

    public function vehicleCreate(): View
    {
        $vehicle = null;

        return view('InstitutionAdmin.TransportVehicleForm', compact('vehicle'));
    }

    public function vehicleStore(StoreTransportVehicleRequest $request): RedirectResponse
    {
        TransportService::saveVehicle(null, $request->validated());

        return redirect()->route('admin.transport.index')->with('success', 'Transport vehicle created.');
    }

    public function vehicleEdit(TransportVehicle $vehicle): View
    {
        return view('InstitutionAdmin.TransportVehicleForm', compact('vehicle'));
    }

    public function vehicleUpdate(StoreTransportVehicleRequest $request, TransportVehicle $vehicle): RedirectResponse
    {
        TransportService::saveVehicle($vehicle, $request->validated());

        return redirect()->route('admin.transport.index')->with('success', 'Transport vehicle updated.');
    }

    public function vehicleDestroy(TransportVehicle $vehicle): RedirectResponse
    {
        TransportService::deleteVehicle($vehicle);

        return redirect()->route('admin.transport.index')->with('success', 'Transport vehicle deleted.');
    }

    public function stopStore(StoreTransportStopRequest $request, TransportRoute $transportRoute): RedirectResponse
    {
        TransportService::addStop($transportRoute, $request->validated());

        return redirect()->route('admin.transport.routes.edit', $transportRoute)->with('success', 'Route stop added.');
    }

    public function stopUpdate(StoreTransportStopRequest $request, TransportRoute $transportRoute, TransportStop $transportStop): RedirectResponse
    {
        abort_unless($transportStop->transport_route_id === $transportRoute->id, 404);
        TransportService::updateStop($transportStop, $request->validated());

        return redirect()->route('admin.transport.routes.edit', $transportRoute)->with('success', 'Route stop updated.');
    }

    public function stopDestroy(TransportRoute $transportRoute, TransportStop $transportStop): RedirectResponse
    {
        abort_unless($transportStop->transport_route_id === $transportRoute->id, 404);
        TransportService::removeStop($transportStop);

        return redirect()->route('admin.transport.routes.edit', $transportRoute)->with('success', 'Route stop deleted.');
    }

    public function assignmentCreate(): View
    {
        $assignment = null;
        $students = $this->activeStudents();
        $routes = TransportRoute::query()->where('is_active', true)->with('stops')->orderBy('name')->get();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        return view('InstitutionAdmin.TransportAssignmentForm', compact('assignment', 'students', 'routes', 'academicYears'));
    }

    public function assignmentEdit(StudentTransportAssignment $assignment): View
    {
        $assignment->load('student.user');
        $students = $this->activeStudents();
        $routes = TransportRoute::query()
            ->where('is_active', true)
            ->orWhere('id', $assignment->transport_route_id)
            ->with('stops')
            ->orderBy('name')
            ->get();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        return view('InstitutionAdmin.TransportAssignmentForm', compact('assignment', 'students', 'routes', 'academicYears'));
    }

    public function assignmentStore(StoreStudentTransportAssignmentRequest $request): RedirectResponse
    {
        TransportService::assignStudent(null, $request->validated() + ['status' => 'active']);

        return redirect()->route('admin.transport.index')->with('success', 'Student transport assignment created.');
    }

    public function assignmentUpdate(StoreStudentTransportAssignmentRequest $request, StudentTransportAssignment $assignment): RedirectResponse
    {
        TransportService::assignStudent($assignment, $request->validated());

        return redirect()->route('admin.transport.index')->with('success', 'Student transport assignment updated.');
    }

    public function assignmentEnd(StudentTransportAssignment $assignment): RedirectResponse
    {
        TransportService::assignStudent($assignment, $assignment->only([
            'student_id',
            'transport_route_id',
            'transport_stop_id',
            'academic_year_id',
            'start_date',
        ]) + [
            'status' => 'inactive',
            'end_date' => $assignment->end_date?->toDateString() ?? max($assignment->start_date->toDateString(), today()->toDateString()),
        ]);

        return redirect()->route('admin.transport.index')->with('success', 'Student transport assignment ended.');
    }

    private function activeStudents()
    {
        return Student::query()
            ->with(['user', 'class'])
            ->where('status', 'active')
            ->whereHas('user')
            ->orderBy('admission_no')
            ->get();
    }
}
