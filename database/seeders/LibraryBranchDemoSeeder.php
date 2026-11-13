<?php

namespace Database\Seeders;

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

        $librarian = User::query()->where('email', 'librarian@school.test')->first();
        if (! $librarian?->branch_id) {
            return;
        }

        $branchId = (int) $librarian->branch_id;
        if (! LibraryLoan::query()
            ->whereNull('returned_at')
            ->whereHas('student', fn ($query) => $query->where('branch_id', $branchId))
            ->exists()) {
            $students = Student::query()
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->orderBy('id')
                ->take(3)
                ->get();
            $copies = LibraryCopy::query()
                ->where('branch_id', $branchId)
                ->where('status', 'available')
                ->orderBy('id')
                ->take(3)
                ->get();

            if ($students->count() < 3 || $copies->count() < 3) {
                throw new RuntimeException('Not enough same-branch students and available library copies for demo loans.');
            }

            foreach (range(0, 2) as $index) {
                $overdue = $index === 0;
                LibraryLoan::create([
                    'copy_id' => $copies[$index]->id,
                    'student_id' => $students[$index]->id,
                    'issued_by' => $librarian->id,
                    'issued_at' => now()->subDays(8),
                    'due_at' => $overdue ? today()->subDays(3) : today()->addDays(7 + $index),
                ]);
                $copies[$index]->update(['status' => 'borrowed']);
            }
        }

        $hasBranchFine = LibraryFine::query()
            ->whereHas('loan.student', fn ($query) => $query->where('branch_id', $branchId))
            ->exists();
        if ($hasBranchFine) {
            return;
        }

        $overdueLoan = LibraryLoan::query()
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', today())
            ->whereHas('student', fn ($query) => $query->where('branch_id', $branchId))
            ->first();

        if (! $overdueLoan) {
            throw new RuntimeException('No overdue same-branch library loan is available for the demo fine.');
        }

        LibraryFine::create([
            'loan_id' => $overdueLoan->id,
            'amount' => '150.00',
            'reason' => 'Fine for a title returned three days after the due date',
        ]);
    }
}
