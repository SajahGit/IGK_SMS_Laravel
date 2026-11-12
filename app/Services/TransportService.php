<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportVehicle;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportService
{
    public static function saveVehicle(?TransportVehicle $vehicle, array $data): TransportVehicle
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $vehicle = $vehicle
                ? TransportVehicle::query()->lockForUpdate()->findOrFail($vehicle->id)
                : new TransportVehicle;
            $before = $vehicle->exists ? $vehicle->toArray() : null;

            if ($vehicle->exists && $data['capacity'] < self::maxAssignedSeatsForVehicle($vehicle)) {
                throw ValidationException::withMessages([
                    'capacity' => 'Vehicle capacity cannot be less than its current active student assignments.',
                ]);
            }
            if ($vehicle->exists && $data['status'] !== 'active' && $vehicle->routes()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Deactivate or reassign this vehicle\'s active routes before placing it out of service.',
                ]);
            }

            $vehicle->fill($data)->save();
            AuditLogger::log($before ? 'updated_transport_vehicle' : 'created_transport_vehicle', $vehicle, $before, $vehicle->toArray());

            return $vehicle;
        });
    }

    public static function saveRoute(?TransportRoute $route, array $data): TransportRoute
    {
        return DB::transaction(function () use ($route, $data) {
            $route = $route ? TransportRoute::query()->lockForUpdate()->findOrFail($route->id) : new TransportRoute;
            $before = $route->exists ? $route->toArray() : null;
            $vehicleId = $data['vehicle_id'] ?? null;

            if (! $vehicleId && $route->exists && $route->assignments()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'An active route with student assignments must keep an assigned vehicle.',
                ]);
            }

            if ($vehicleId) {
                $vehicle = TransportVehicle::query()->lockForUpdate()->findOrFail($vehicleId);
                if ($vehicle->status !== 'active') {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'Only active vehicles can be assigned to a route.',
                    ]);
                }
                if (self::maxAssignedSeatsForVehicle($vehicle, $route->id) > $vehicle->capacity) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'The vehicle does not have enough seats for this route and its existing assignments.',
                    ]);
                }
            }

            $route->fill($data + ['is_active' => false])->save();
            AuditLogger::log($before ? 'updated_transport_route' : 'created_transport_route', $route, $before, $route->toArray());

            return $route;
        });
    }

    public static function addStop(TransportRoute $route, array $data): TransportStop
    {
        $stop = DB::transaction(function () use ($route, $data) {
            $route = TransportRoute::query()->lockForUpdate()->findOrFail($route->id);
            if (TransportStop::query()
                ->where('transport_route_id', $route->id)
                ->where('sequence', $data['sequence'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'sequence' => 'This route already has a stop at that sequence number.',
                ]);
            }

            $stop = $route->stops()->create($data);
            AuditLogger::log('created_transport_stop', $stop, null, $stop->toArray());

            return $stop;
        });

        return $stop;
    }

    public static function assignStudent(?StudentTransportAssignment $assignment, array $data): StudentTransportAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $route = TransportRoute::query()->lockForUpdate()->findOrFail($data['transport_route_id']);
            $stop = TransportStop::query()->lockForUpdate()->findOrFail($data['transport_stop_id']);
            $studentId = (int) $data['student_id'];
            $yearId = (int) $data['academic_year_id'];
            $student = Student::query()->lockForUpdate()->findOrFail($studentId);
            $academicYear = AcademicYear::query()->findOrFail($yearId);
            if (($route->branch_id !== null || $student->branch_id !== null)
                && (int) $route->branch_id !== (int) $student->branch_id) {
                throw ValidationException::withMessages([
                    'student_id' => 'The student and transport route must belong to the same campus.',
                ]);
            }

            $yearStart = $academicYear->start_date->toDateString();
            $yearEnd = $academicYear->end_date->toDateString();
            $startDate = $data['start_date'] instanceof \DateTimeInterface
                ? $data['start_date']->format('Y-m-d')
                : (string) $data['start_date'];
            $endDate = empty($data['end_date'])
                ? null
                : ($data['end_date'] instanceof \DateTimeInterface
                    ? $data['end_date']->format('Y-m-d')
                    : (string) $data['end_date']);
            $data['start_date'] = $startDate;
            $data['end_date'] = $endDate;
            if ($student->status !== 'active') {
                throw ValidationException::withMessages([
                    'student_id' => 'Only active students can be assigned to school transport.',
                ]);
            }
            if ($startDate < $yearStart || $startDate > $yearEnd || ($endDate && ($endDate < $startDate || $endDate > $yearEnd))) {
                throw ValidationException::withMessages([
                    'start_date' => 'Assignment dates must fall within the selected academic year.',
                ]);
            }

            if ($stop->transport_route_id !== $route->id) {
                throw ValidationException::withMessages([
                    'transport_stop_id' => 'The selected stop does not belong to the selected route.',
                ]);
            }

            $current = $assignment
                ? StudentTransportAssignment::query()->lockForUpdate()->findOrFail($assignment->id)
                : null;
            $before = $current?->toArray();
            $status = $data['status'] ?? 'active';

            if ($status === 'active') {
                if (! $route->is_active) {
                    throw ValidationException::withMessages([
                        'transport_route_id' => 'Students can only be assigned to an active route.',
                    ]);
                }

                if (! $route->vehicle_id) {
                    throw ValidationException::withMessages([
                        'transport_route_id' => 'Assign an active vehicle to this route before assigning students.',
                    ]);
                }

                $existing = StudentTransportAssignment::query()
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $yearId)
                    ->where('status', 'active')
                    ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                    ->exists();
                if ($existing) {
                    throw ValidationException::withMessages([
                        'student_id' => 'This student already has an active transport assignment for the selected academic year.',
                    ]);
                }

                $vehicle = TransportVehicle::query()->lockForUpdate()->findOrFail($route->vehicle_id);
                if ($vehicle->status !== 'active') {
                    throw ValidationException::withMessages([
                        'transport_route_id' => 'The route vehicle is not currently active.',
                    ]);
                }
                $seats = StudentTransportAssignment::query()
                    ->whereHas('route', fn ($query) => $query->where('vehicle_id', $vehicle->id))
                    ->where('academic_year_id', $yearId)
                    ->where('status', 'active')
                    ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                    ->count();
                if ($seats >= $vehicle->capacity) {
                    throw ValidationException::withMessages([
                        'transport_route_id' => 'The assigned vehicle has no remaining seats for this academic year.',
                    ]);
                }
            }

            $current ??= new StudentTransportAssignment;
            $current->fill($data + ['status' => $status])->save();
            AuditLogger::log($before ? 'updated_student_transport_assignment' : 'created_student_transport_assignment', $current, $before, $current->toArray());

            return $current;
        });
    }

    public static function removeStop(TransportStop $stop): void
    {
        DB::transaction(function () use ($stop) {
            $stop = TransportStop::query()->lockForUpdate()->findOrFail($stop->id);
            if ($stop->assignments()->exists()) {
                throw ValidationException::withMessages([
                    'stop' => 'A stop with student assignments cannot be deleted.',
                ]);
            }

            $before = $stop->toArray();
            $stop->delete();
            AuditLogger::log('deleted_transport_stop', $stop, $before, null);
        });
    }

    public static function updateStop(TransportStop $stop, array $data): TransportStop
    {
        return DB::transaction(function () use ($stop, $data) {
            $stop = TransportStop::query()->lockForUpdate()->findOrFail($stop->id);
            $duplicate = TransportStop::query()
                ->where('transport_route_id', $stop->transport_route_id)
                ->where('sequence', $data['sequence'])
                ->whereKeyNot($stop->id)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'sequence' => 'This route already has a stop at that sequence number.',
                ]);
            }

            $before = $stop->toArray();
            $stop->update($data);
            AuditLogger::log('updated_transport_stop', $stop, $before, $stop->toArray());

            return $stop;
        });
    }

    public static function deleteRoute(TransportRoute $route): void
    {
        DB::transaction(function () use ($route) {
            $route = TransportRoute::query()->lockForUpdate()->findOrFail($route->id);
            if ($route->assignments()->exists() || $route->stops()->exists()) {
                throw ValidationException::withMessages([
                    'route' => 'A route with stops or assignment history cannot be deleted. Deactivate it instead.',
                ]);
            }

            $before = $route->toArray();
            $route->delete();
            AuditLogger::log('deleted_transport_route', $route, $before, null);
        });
    }

    public static function deleteVehicle(TransportVehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            $vehicle = TransportVehicle::query()->lockForUpdate()->findOrFail($vehicle->id);
            if ($vehicle->routes()->exists()) {
                throw ValidationException::withMessages([
                    'vehicle' => 'A vehicle assigned to a route cannot be deleted. Unassign or deactivate it instead.',
                ]);
            }

            $before = $vehicle->toArray();
            $vehicle->delete();
            AuditLogger::log('deleted_transport_vehicle', $vehicle, $before, null);
        });
    }

    private static function maxAssignedSeatsForVehicle(TransportVehicle $vehicle, ?int $includeRouteId = null): int
    {
        $yearlySeatCounts = StudentTransportAssignment::query()
            ->selectRaw('academic_year_id, COUNT(*) AS seats')
            ->whereHas('route', function ($query) use ($vehicle, $includeRouteId) {
                $query->where('vehicle_id', $vehicle->id);
                if ($includeRouteId) {
                    $query->orWhere('id', $includeRouteId);
                }
            })
            ->where('status', 'active')
            ->groupBy('academic_year_id')
            ->get();

        return (int) ($yearlySeatCounts->max('seats') ?? 0);
    }
}
