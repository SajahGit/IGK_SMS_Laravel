<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BranchAccountingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Sample accounting data may only be seeded in local or testing environments.');
        }

        $this->seedBranches();
        $this->seedChartOfAccounts();
        $this->seedManualEntries();
        JournalService::syncFromBilling(); // automatic postings for invoices & payments
    }

    private function seedBranches(): void
    {
        $branches = [
            ['code' => 'MAIN', 'name' => 'IGK Main Campus', 'city' => 'Colombo', 'address' => '12 School Lane, Colombo 07', 'phone' => '0112345678', 'email' => 'main@igk.test', 'principal_name' => 'Dr. A. Rahman', 'is_main' => true],
            ['code' => 'KAN', 'name' => 'IGK Kandy Campus', 'city' => 'Kandy', 'address' => '45 Hill Street, Kandy', 'phone' => '0812234567', 'email' => 'kandy@igk.test', 'principal_name' => 'Mrs. S. Fernando', 'is_main' => false],
            ['code' => 'GAL', 'name' => 'IGK Galle Campus', 'city' => 'Galle', 'address' => '8 Fort Road, Galle', 'phone' => '0912223344', 'email' => 'galle@igk.test', 'principal_name' => 'Mr. M. Nazeer', 'is_main' => false],
        ];
        foreach ($branches as $b) {
            Branch::firstOrCreate(['code' => $b['code']], $b + ['is_active' => true]);
        }

        // Keep the sample campus assignment consistent across parent records.
        $ids = Branch::orderBy('id')->pluck('id')->all();
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'branch_id')) {
            DB::table('classes')->whereNull('branch_id')->orderBy('id')->pluck('id')->each(function ($id) use ($ids) {
                DB::table('classes')->where('id', $id)->update(['branch_id' => $ids[$id % count($ids)]]);
            });
        }
        $mainBranchId = Branch::where('is_main', true)->value('id');
        if ($mainBranchId && Schema::hasTable('employees') && Schema::hasColumn('employees', 'branch_id')) {
            DB::table('employees')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'branch_id')) {
            DB::table('students as students')
                ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
                ->select(['students.id', 'classes.branch_id as class_branch_id'])
                ->get()
                ->each(function ($student) use ($ids): void {
                    DB::table('students')->where('id', $student->id)->update([
                        'branch_id' => $student->class_branch_id ?? $ids[$student->id % count($ids)],
                    ]);
                });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'branch_id')) {
            DB::table('invoices as invoices')
                ->join('students', 'students.id', '=', 'invoices.student_id')
                ->select(['invoices.id', 'students.branch_id as student_branch_id'])
                ->get()
                ->each(function ($invoice) use ($ids): void {
                    DB::table('invoices')->where('id', $invoice->id)->update([
                        'branch_id' => $invoice->student_branch_id ?? $ids[$invoice->id % count($ids)],
                    ]);
                });
        }

        if ($mainBranchId) {
            if (Schema::hasTable('transport_routes') && Schema::hasTable('student_transport_assignments')) {
                foreach (DB::table('transport_routes')->get(['id', 'vehicle_id']) as $route) {
                    $branchId = DB::table('student_transport_assignments as assignments')
                        ->join('students', 'students.id', '=', 'assignments.student_id')
                        ->where('assignments.transport_route_id', $route->id)
                        ->orderBy('assignments.id')
                        ->value('students.branch_id');

                    if ($branchId) {
                        DB::table('transport_routes')->where('id', $route->id)->update(['branch_id' => $branchId]);
                        if ($route->vehicle_id) {
                            DB::table('transport_vehicles')->where('id', $route->vehicle_id)->update(['branch_id' => $branchId]);
                        }
                    }
                }
            }

            if (Schema::hasTable('hostels') && Schema::hasTable('hostel_assignments')) {
                DB::table('hostel_assignments as assignments')
                    ->join('hostel_rooms', 'hostel_rooms.id', '=', 'assignments.hostel_room_id')
                    ->join('students', 'students.id', '=', 'assignments.student_id')
                    ->select(['hostel_rooms.hostel_id', 'students.branch_id'])
                    ->get()
                    ->each(function ($assignment): void {
                        if ($assignment->branch_id) {
                            DB::table('hostels')->where('id', $assignment->hostel_id)->update(['branch_id' => $assignment->branch_id]);
                        }
                    });
            }

            if (Schema::hasTable('library_copies') && Schema::hasTable('library_loans')) {
                DB::table('library_loans')
                    ->join('students', 'students.id', '=', 'library_loans.student_id')
                    ->select(['library_loans.copy_id', 'students.branch_id'])
                    ->get()
                    ->each(function ($loan): void {
                        if ($loan->branch_id) {
                            DB::table('library_copies')->where('id', $loan->copy_id)->update(['branch_id' => $loan->branch_id]);
                        }
                    });
            }

            foreach ([
                'inventory_items', 'suppliers', 'purchase_orders', 'hostels',
                'cafeteria_items', 'transport_vehicles', 'transport_routes',
                'library_copies', 'payroll_runs', 'staff_attendance',
            ] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                    DB::table($table)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
                }
            }
        }

        foreach (DB::table('students')->whereNotNull('branch_id')->get(['user_id', 'branch_id']) as $student) {
            DB::table('users')->where('id', $student->user_id)->update(['branch_id' => $student->branch_id]);
        }
        foreach (DB::table('employees')->whereNotNull('branch_id')->whereNotNull('user_id')->get(['user_id', 'branch_id']) as $employee) {
            DB::table('users')->where('id', $employee->user_id)->update(['branch_id' => $employee->branch_id]);
        }
        if ($mainBranchId) {
            DB::table('users')
                ->whereNull('branch_id')
                ->whereIn('role', ['teacher', 'registrar', 'accountant', 'librarian', 'staff'])
                ->update(['branch_id' => $mainBranchId]);
        }
    }

    private function seedChartOfAccounts(): void
    {
        $accounts = [
            ['1000', 'Cash in Hand', 'asset'], ['1010', 'Bank Account', 'asset'], ['1100', 'Student Fees Receivable', 'asset'],
            ['1200', 'Inventory & Stock', 'asset'], ['1500', 'Land & Buildings', 'asset'], ['1510', 'Furniture & Equipment', 'asset'],
            ['2000', 'Accounts Payable', 'liability'], ['2100', 'Salaries Payable', 'liability'], ['2110', 'EPF Payable', 'liability'],
            ['2120', 'ETF Payable', 'liability'], ['2130', 'APIT Payable', 'liability'], ['2140', 'Other Payroll Deductions Payable', 'liability'], ['2200', 'Fees Received in Advance', 'liability'],
            ['3000', 'Owner\'s Capital', 'equity'], ['3100', 'Retained Earnings', 'equity'],
            ['4000', 'Tuition Fee Income', 'income'], ['4010', 'Student Registration Fee Income', 'income'], ['4020', 'Books Fee Income', 'income'],
            ['4030', 'Course Fee Income', 'income'], ['4040', 'Exam Fee Income', 'income'], ['4050', 'Laboratory Fee Income', 'income'],
            ['4100', 'Transport Fee Income', 'income'], ['4110', 'Hostel Fee Income', 'income'], ['4120', 'Cafeteria Income', 'income'], ['4900', 'Other Income', 'income'],
            ['4130', 'Admission and Application Fee Income', 'income'], ['4140', 'Uniform and Learning Kit Income', 'income'],
            ['4150', 'Activities and Clubs Income', 'income'], ['4160', 'After-school Programme Income', 'income'],
            ['4170', 'Donations and Sponsorship Income', 'income'], ['4180', 'Grants and Subsidies Income', 'income'], ['4190', 'Bank Interest Income', 'income'],
            ['5000', 'Salaries & Wages', 'expense'], ['5010', 'Employer EPF Contribution', 'expense'], ['5020', 'Employer ETF Contribution', 'expense'],
            ['5100', 'Utilities', 'expense'], ['5110', 'Electricity', 'expense'], ['5120', 'Water', 'expense'], ['5130', 'Internet and Telecommunications', 'expense'],
            ['5200', 'Stationery & Supplies', 'expense'], ['5210', 'Books and Learning Materials', 'expense'], ['5220', 'Examination and Printing', 'expense'],
            ['5300', 'Repairs & Maintenance', 'expense'], ['5310', 'Cleaning and Sanitation', 'expense'], ['5320', 'Security Services', 'expense'],
            ['5400', 'Transport Running Costs', 'expense'], ['5410', 'Fuel', 'expense'], ['5420', 'Vehicle Maintenance', 'expense'],
            ['5500', 'Rent and Premises', 'expense'], ['5510', 'Insurance', 'expense'], ['5600', 'Professional and Audit Fees', 'expense'],
            ['5700', 'Bank Charges', 'expense'], ['5900', 'Other Expenses', 'expense'],
        ];
        foreach ($accounts as [$code, $name, $type]) {
            Account::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'is_active' => true]);
        }
    }

    private function seedManualEntries(): void
    {
        $id = fn (string $code) => Account::where('code', $code)->value('id');
        $main = Branch::where('code', 'MAIN')->value('id');
        $kandy = Branch::where('code', 'KAN')->value('id');

        $entries = [
            ['SEED-CAP', 'Opening capital introduced', now()->subDays(60), $main, [['1010', 500000, 0], ['3000', 0, 500000]]],
            ['SEED-UTIL', 'Electricity bill - main campus', now()->subDays(25), $main, [['5100', 18500, 0], ['1010', 0, 18500]]],
            ['SEED-SAL', 'Monthly salaries paid', now()->subDays(15), $main, [['5000', 250000, 0], ['1010', 0, 250000]]],
            ['SEED-STAT', 'Stationery purchase - Kandy', now()->subDays(12), $kandy, [['5200', 7200, 0], ['1000', 0, 7200]]],
            ['SEED-REG', 'Student registration fees (cash)', now()->subDays(30), $main, [['1000', 45000, 0], ['4010', 0, 45000]]],
            ['SEED-BOOK', 'Books fee collected', now()->subDays(28), $kandy, [['1010', 32000, 0], ['4020', 0, 32000]]],
            ['SEED-EXAM', 'Exam fee & additional draft papers', now()->subDays(10), $main, [['1000', 12500, 0], ['4040', 0, 12500]]],
            ['SEED-LAB', 'Laboratory fees', now()->subDays(8), $main, [['1010', 21000, 0], ['4050', 0, 21000]]],
            ['SEED-REPAIR', 'Classroom repairs', now()->subDays(5), $main, [['5300', 26000, 0], ['2000', 0, 26000]]],
        ];

        foreach ($entries as [$ref, $desc, $date, $branch, $lines]) {
            if (JournalEntry::where('reference', $ref)->exists()) {
                continue;
            }
            JournalService::post(
                ['entry_date' => $date->toDateString(), 'description' => $desc, 'reference' => $ref, 'branch_id' => $branch],
                array_map(fn ($l) => ['account_id' => $id($l[0]), 'debit' => $l[1], 'credit' => $l[2]], $lines)
            );
        }
    }
}
