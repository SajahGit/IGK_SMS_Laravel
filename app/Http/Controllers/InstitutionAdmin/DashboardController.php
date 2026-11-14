<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = BranchContext::activeBranchId();
        $attendanceDays = collect(range(6, 0))
            ->map(fn (int $daysAgo) => today()->subDays($daysAgo));
        $attendanceByDay = Attendance::query()
            ->whereBetween('date', [
                $attendanceDays->first()->toDateString(),
                $attendanceDays->last()->toDateString(),
            ])
            ->selectRaw('date, status, COUNT(*) AS total')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => CarbonImmutable::parse($row->date)->toDateString());

        $collectionMonths = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => CarbonImmutable::now()->startOfMonth()->subMonths($monthsAgo));

        return view('InstitutionAdmin.InstitutionAdminDashboard', [
            'studentCount' => Student::count(),
            'teacherCount' => Teacher::query()
                ->when($branchId !== null, fn ($query) => $query->whereHas(
                    'user',
                    fn ($userQuery) => $userQuery->where('branch_id', $branchId)
                ))
                ->count(),
            'classCount' => SchoolClass::count(),
            'subjectCount' => Subject::count(),
            'guardianCount' => Guardian::inActiveBranch()->count(),
            'latestStudents' => Student::with(['user', 'class'])->latest()->take(5)->get(),
            'attendanceTrend' => [
                'labels' => $attendanceDays->map(fn ($date) => $date->format('d M'))->all(),
                'present' => $attendanceDays->map(fn ($date) => (int) $attendanceByDay
                    ->get($date->toDateString(), collect())
                    ->firstWhere('status', 'present')?->total)->all(),
                'absent' => $attendanceDays->map(fn ($date) => (int) $attendanceByDay
                    ->get($date->toDateString(), collect())
                    ->firstWhere('status', 'absent')?->total)->all(),
            ],
            'feeCollectionTrend' => [
                'labels' => $collectionMonths->map(fn ($month) => $month->format('M Y'))->all(),
                'totals' => $collectionMonths->map(fn ($month) => (float) Payment::query()
                    ->whereNull('reversed_at')
                    ->whereBetween('paid_at', [$month->toDateString(), $month->endOfMonth()->toDateString()])
                    ->sum('amount'))->all(),
            ],
        ]);
    }
}
