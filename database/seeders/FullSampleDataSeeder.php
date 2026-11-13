<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/** One command that fills every module with sample data: php artisan db:seed --class=FullSampleDataSeeder */
class FullSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Sample data may only be seeded in local or testing environments.');
        }

        $this->call(DemoDataSeeder::class);            // academic, students, fees, invoices, payments, HR, payroll, inventory, procurement, transport, LMS, hostel, cafeteria
        $this->call(CompleteSampleDataSeeder::class);  // library, fee items, curriculum, reversal, messages, documents
        $this->call(BranchAccountingSeeder::class);    // branches, chart of accounts, journals (posts final billing state)
        $this->call(LibraryBranchDemoSeeder::class);   // branch-visible library loans and fines
        $this->call(CafeteriaStudentDemoSeeder::class); // branch-visible menu and orders for the demo student
        $this->call(TeacherLearningDemoSeeder::class); // demo exams, course modules and past-paper PDFs
        $this->call(ExtendedScopeDemoSeeder::class);   // pending online payment, biometric events and expired API token
    }
}
