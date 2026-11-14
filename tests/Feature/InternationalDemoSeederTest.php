<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class InternationalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    public function test_full_sample_dataset_covers_international_school_workflows_and_is_idempotent(): void
    {
        DB::table('classes')->insert([
            ['name' => 'Grade 10', 'section' => 'A', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Grade 10', 'section' => 'B', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])->assertExitCode(0);

        $this->assertSame(
            240,
            DB::table('users')->where('email', 'like', 'demo.student.%@school.test')->count()
        );
        $demoStudents = DB::table('students')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('users.email', 'like', 'demo.student.%@school.test');
        $this->assertSame(0, (clone $demoStudents)->whereNull('document_type')->count());
        $this->assertSame(0, (clone $demoStudents)->whereNull('issuing_country')->count());
        $this->assertSame(0, (clone $demoStudents)->whereNull('document_number')->count());
        $this->assertSame(12, (clone $demoStudents)->distinct()->count('issuing_country'));
        $this->assertGreaterThan(0, (clone $demoStudents)->where('document_type', 'passport')->count());
        $this->assertGreaterThan(0, (clone $demoStudents)->where('document_type', 'national_id')->count());
        $demoGuardianPhones = DB::table('guardians')
            ->join('users', 'users.id', '=', 'guardians.user_id')
            ->where('users.email', 'like', 'demo.guardian.%@school.test')
            ->get(['guardians.nationality', 'guardians.phone']);
        $callingCodes = [
            'Sri Lankan' => '+94',
            'Indian' => '+91',
            'British' => '+44',
            'American' => '+1',
            'Emirati' => '+971',
            'French' => '+33',
            'Kenyan' => '+254',
            'Chinese' => '+86',
            'Japanese' => '+81',
            'Singaporean' => '+65',
            'South African' => '+27',
            'Australian' => '+61',
        ];
        foreach ($demoGuardianPhones as $guardianPhone) {
            $this->assertStringStartsWith($callingCodes[$guardianPhone->nationality], $guardianPhone->phone);
            $this->assertMatchesRegularExpression('/\A\+[1-9][0-9]{6,14}\z/', $guardianPhone->phone);
        }
        $this->assertSame(12, DB::table('programs')->whereIn('code', [
            'IBPYP', 'CAMPRIM', 'AMER-ELEM', 'IBMYP', 'CAMLS', 'AMER-MS',
            'CA-IGCSE', 'ED-IGCSE', 'US-HS', 'IBDP', 'CAM-AL', 'ED-AL',
        ])->count());
        $languageCodes = ['SIN', 'HIN', 'JPN', 'SWA', 'MAL', 'ZUL', 'AFR', 'TAM', 'MAN', 'ARA', 'FRE', 'SPA'];
        $languageSubjectIds = DB::table('subjects')->whereIn('code', $languageCodes)->pluck('id');
        $this->assertCount(count($languageCodes), $languageSubjectIds);
        $this->assertGreaterThanOrEqual(
            12,
            DB::table('curricula')->whereIn('subject_id', $languageSubjectIds)->distinct()->count('program_id')
        );
        foreach (['Nursery', 'Pre-K', 'Reception'] as $level) {
            $this->assertDatabaseHas('classes', ['name' => $level]);
        }
        foreach (range(1, 12) as $grade) {
            $this->assertDatabaseHas('classes', ['name' => "Grade {$grade}"]);
        }
        foreach (range(1, 9) as $year) {
            $this->assertDatabaseHas('classes', ['name' => "Year {$year}"]);
        }
        $this->assertEqualsCanonicalizing([
            'IB 1-7',
            'Cambridge Achievement',
            'IGCSE A*-G',
            'Edexcel International GCSE 9-1',
            'A-Level A*-E',
            'American GPA',
        ], DB::table('grade_scales')->orderBy('name')->pluck('name')->all());
        $this->assertSame(5, DB::table('announcements')->whereIn('title', [
            '2026-2027 International School Academic Calendar',
            'IB MYP Grade 10 Assessment Schedule',
            'IB MYP Grade 10 Mark Submission',
            'Grade 10 Family-Teacher Conference',
            'Registrar Term 1 Admissions Review',
        ])->count());
        $this->assertDatabaseHas('payhere_transactions', [
            'order_id' => 'IGK-DEMO-PAYHERE-'.DB::table('payhere_transactions')->value('invoice_id'),
            'status' => 'pending',
        ]);
        $this->assertSame(1, DB::table('biometric_devices')->where('name', 'IGK Main Campus North Gate Reader')->count());
        $this->assertSame(2, DB::table('biometric_events')->where('event_id', 'like', 'IGK-DEMO-%')->count());
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Expired international student API sample',
        ]);
        $this->assertTrue(DB::table('personal_access_tokens')
            ->where('name', 'Expired international student API sample')
            ->where('expires_at', '<', now())
            ->exists());

        foreach ([
            'classes',
            'teachers',
            'guardians',
            'class_subject',
            'student_subject_enrollments',
            'timetable_slots',
            'attendance_sessions',
            'attendance',
            'exams',
            'results',
            'term_results',
            'fee_structures',
            'invoices',
            'payments',
            'audit_logs',
        ] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "Expected seeded records in {$table}.");
        }

        $currencies = DB::table('fee_structures')->distinct()->orderBy('currency')->pluck('currency')->all();
        $this->assertEqualsCanonicalizing(['AED', 'EUR', 'GBP', 'LKR', 'USD'], $currencies);
        $this->assertFalse(DB::table('classes')->whereNull('program_id')->exists());
        $this->assertDatabaseHas('classes', [
            'name' => 'Grade 10',
            'section' => 'A',
            'program_id' => DB::table('programs')->where('code', 'IBMYP')->value('id'),
        ]);
        $this->assertDatabaseHas('classes', [
            'name' => 'Grade 10',
            'section' => 'B',
            'program_id' => DB::table('programs')->where('code', 'CA-IGCSE')->value('id'),
        ]);
        $this->assertFalse(DB::table('students')
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->whereNotNull('students.program_id')
            ->whereColumn('students.program_id', '!=', 'classes.program_id')
            ->exists());

        $counts = [
            'users',
            'students',
            'guardians',
            'classes',
            'class_subject',
            'student_subject_enrollments',
            'attendance',
            'exams',
            'results',
            'term_results',
            'announcements',
            'fee_structures',
            'invoices',
            'payments',
            'payhere_transactions',
            'biometric_devices',
            'biometric_events',
            'personal_access_tokens',
        ];
        $before = collect($counts)->mapWithKeys(fn (string $table) => [
            $table => DB::table($table)->count(),
        ]);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])->assertExitCode(0);

        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "Re-seeding duplicated records in {$table}.");
        }
    }
}
