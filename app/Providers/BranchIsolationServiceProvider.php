<?php

namespace App\Providers;

use App\Models\CafeteriaItem;
use App\Models\Employee;
use App\Models\Hostel;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LibraryCopy;
use App\Models\PayrollRun;
use App\Models\PurchaseOrder;
use App\Models\SchoolClass;
use App\Models\Scopes\BranchOwned;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Supplier;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

/**
 * Multi-Branch Management - Phase 2.
 *
 * Applies the BranchOwned global scope to operational models added after
 * Phase 1 and stamps branch_id from the active campus. Global administrators
 * use the selected campus; unassigned staff are denied branch-owned data when
 * campuses are configured, while legacy single-campus installations remain compatible.
 */
class BranchIsolationServiceProvider extends ServiceProvider
{
    /** @var list<class-string<Model>> Phase 2 models (scope is added here). */
    public const PHASE_TWO_MODELS = [
        InventoryItem::class, Supplier::class, PurchaseOrder::class,
        Hostel::class, CafeteriaItem::class,
        TransportVehicle::class, TransportRoute::class,
        LibraryCopy::class, PayrollRun::class, StaffAttendance::class,
    ];

    /** @var list<class-string<Model>> Phase 1 models (scope already in the model). */
    public const PHASE_ONE_MODELS = [
        Student::class, SchoolClass::class, Invoice::class, Employee::class, JournalEntry::class,
    ];

    public function boot(): void
    {
        foreach (self::PHASE_TWO_MODELS as $model) {
            $model::addGlobalScope(new BranchOwned);
        }

        foreach (array_merge(self::PHASE_ONE_MODELS, self::PHASE_TWO_MODELS) as $model) {
            $model::creating(function (Model $record): void {
                if ($record->getAttribute('branch_id') !== null) {
                    return;
                }

                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                if (! $user) {
                    return;
                }

                $branchId = BranchContext::activeBranchId($user);
                if ($branchId !== null) {
                    $record->setAttribute('branch_id', $branchId);

                    return;
                }

                if (! BranchContext::isGlobalAdmin($user) && BranchContext::hasActiveBranches()) {
                    throw ValidationException::withMessages([
                        'branch_id' => 'Assign this account to a campus before creating branch records.',
                    ]);
                }
            });
        }
    }
}
