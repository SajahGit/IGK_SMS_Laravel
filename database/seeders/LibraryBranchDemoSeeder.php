<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LibraryCopy;
use App\Models\LibraryFine;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LibraryBranchDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Library demo data may only be seeded outside production.');
        }

        $librarian = User::query()->where('email', 'librarian@school.test')->firstOrFail();
        $branchId = $librarian->branch_id
            ?? Branch::withoutGlobalScope('assigned-branch')
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('id')
                ->value('id');

        if (! $branchId) {
            throw new RuntimeException('An active branch is required for librarian demo data.');
        }

        if ($librarian->branch_id === null) {
            $librarian->update(['branch_id' => $branchId]);
        }

        $activeBranchLoans = fn () => LibraryLoan::query()
            ->whereNull('returned_at')
            ->whereHas('student', fn ($query) => $query->withoutGlobalScopes()->where('branch_id', $branchId));
        $missingLoans = max(0, 3 - $activeBranchLoans()->count());
        $hasUnpaidBranchFine = LibraryFine::query()
            ->whereNull('paid_at')
            ->whereHas('loan.student', fn ($query) => $query->withoutGlobalScopes()->where('branch_id', $branchId))
            ->exists();
        $overdueLoanWithoutFine = LibraryLoan::query()
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', today())
            ->whereDoesntHave('fine')
            ->whereHas('student', fn ($query) => $query->withoutGlobalScopes()->where('branch_id', $branchId))
            ->first();
        $needsOverdueLoan = ! $hasUnpaidBranchFine && ! $overdueLoanWithoutFine;
        if ($needsOverdueLoan) {
            $missingLoans = max(1, $missingLoans);
        }

        if ($missingLoans > 0) {
            $activeStudentIds = $activeBranchLoans()->pluck('student_id');
            $students = Student::query()
                ->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->when($activeStudentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $activeStudentIds))
                ->orderBy('id')
                ->take($missingLoans)
                ->get();
            $copies = LibraryCopy::query()
                ->where('branch_id', $branchId)
                ->where('status', 'available')
                ->orderBy('id')
                ->take($missingLoans)
                ->get();

            if ($students->count() < $missingLoans || $copies->count() < $missingLoans) {
                throw new RuntimeException('Not enough same-branch students and available library copies for demo loans.');
            }

            foreach (range(0, $missingLoans - 1) as $index) {
                $overdue = $needsOverdueLoan && $index === 0;
                $loan = LibraryLoan::create([
                    'copy_id' => $copies[$index]->id,
                    'student_id' => $students[$index]->id,
                    'issued_by' => $librarian->id,
                    'issued_at' => now()->subDays(8),
                    'due_at' => $overdue ? today()->subDays(3) : today()->addDays(7 + $index),
                ]);
                if ($overdue) {
                    $overdueLoanWithoutFine = $loan;
                }
                $copies[$index]->update(['status' => 'borrowed']);
            }
        }

        if (! $hasUnpaidBranchFine && ! $overdueLoanWithoutFine) {
            throw new RuntimeException('No overdue same-branch library loan is available for the demo fine.');
        }

        if (! $hasUnpaidBranchFine) {
            LibraryFine::firstOrCreate([
                'loan_id' => $overdueLoanWithoutFine->id,
            ], [
                'amount' => '150.00',
                'reason' => 'Fine for a title returned three days after the due date',
            ]);
        }
    }
}
