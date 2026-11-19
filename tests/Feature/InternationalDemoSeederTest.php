<?php

namespace Tests\Feature;

use App\Models\User;
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
        $this->assertSame(3, DB::table('branches')->count());
        $this->assertSame(0, DB::table('classes')->whereNull('branch_id')->count());
        $this->assertSame(0, DB::table('students')->whereNull('branch_id')->count());
        $this->assertFalse(DB::table('classes as classes')
            ->leftJoin('programs', 'programs.id', '=', 'classes.program_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'classes.academic_year_id')
            ->leftJoin('branches', 'branches.id', '=', 'classes.branch_id')
            ->whereNull('programs.id')
            ->orWhereNull('academic_years.id')
            ->orWhereNull('branches.id')
            ->exists());
        $this->assertFalse(DB::table('students as students')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'students.academic_year_id')
            ->leftJoin('branches', 'branches.id', '=', 'students.branch_id')
            ->whereNull('classes.id')
            ->orWhereNull('programs.id')
            ->orWhereNull('academic_years.id')
            ->orWhereNull('branches.id')
            ->orWhereColumn('students.branch_id', '!=', 'classes.branch_id')
            ->exists());
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
        $this->assertSame(1, DB::table('academic_years')
            ->where('name', '2026-2027')
            ->whereDate('start_date', '2026-09-01')
            ->whereDate('end_date', '2027-07-16')
            ->count());
        $this->assertSame(3, DB::table('terms')
            ->where('academic_year_id', DB::table('academic_years')->where('name', '2026-2027')->value('id'))
            ->count());
        $currentTermId = DB::table('terms')->where('is_current', true)->value('id');
        $timetableClash = DB::table('timetable_slots as first_slot')
            ->join('class_subject as first_subject', 'first_subject.id', '=', 'first_slot.class_subject_id')
            ->join('classes as first_class', 'first_class.id', '=', 'first_subject.class_id')
            ->join('timetable_slots as second_slot', 'second_slot.id', '>', 'first_slot.id')
            ->join('class_subject as second_subject', 'second_subject.id', '=', 'second_slot.class_subject_id')
            ->join('classes as second_class', 'second_class.id', '=', 'second_subject.class_id')
            ->where('first_subject.term_id', $currentTermId)
            ->where('second_subject.term_id', $currentTermId)
            ->whereColumn('first_slot.day_of_week', 'second_slot.day_of_week')
            ->whereColumn('first_slot.start_time', '<', 'second_slot.end_time')
            ->whereColumn('first_slot.end_time', '>', 'second_slot.start_time')
            ->where(function ($query): void {
                $query->whereColumn('first_subject.class_id', 'second_subject.class_id')
                    ->orWhere(function ($teacherQuery): void {
                        $teacherQuery->whereNotNull('first_subject.teacher_id')
                            ->whereColumn('first_subject.teacher_id', 'second_subject.teacher_id');
                    })
                    ->orWhere(function ($roomQuery): void {
                        $roomQuery->whereColumn('first_slot.room', 'second_slot.room')
                            ->whereColumn('first_class.branch_id', 'second_class.branch_id');
                    });
            })
            ->exists();
        $this->assertFalse($timetableClash, 'Current-term timetable slots must not overlap by class, teacher, or room.');

        $demoAccounts = [
            'superadmin@school.test' => ['Super Admin', true, false],
            'admin@school.test' => ['Institution Admin', true, false],
            'registrar@school.test' => ['Registrar', true, false],
            'teacher@school.test' => ['Teacher', true, false],
            'accountant@school.test' => ['Accountant', true, false],
            'student@school.test' => ['Student', false, true],
        ];
        $demoUserIds = [];
        foreach ($demoAccounts as $email => [$roleName, $hasStaffProfile, $hasStudentProfile]) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            $this->assertNotNull($userId, "Missing demo account {$email}.");
            $demoUserIds[] = $userId;
            $this->assertTrue(User::query()->findOrFail($userId)->hasRole($roleName), "Demo account {$email} is not assigned the {$roleName} role.");
            if ($hasStaffProfile) {
                $this->assertTrue(DB::table('employees')->where('user_id', $userId)->exists());
            }
            if ($hasStudentProfile) {
                $this->assertTrue(DB::table('students')->where('user_id', $userId)->exists());
            }
        }
        $this->assertCount(count($demoAccounts), array_unique($demoUserIds));
        $this->assertSame(0, DB::table('class_subject')
            ->leftJoin('classes', 'classes.id', '=', 'class_subject.class_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'class_subject.subject_id')
            ->leftJoin('teachers', 'teachers.id', '=', 'class_subject.teacher_id')
            ->whereNull('classes.id')
            ->orWhereNull('subjects.id')
            ->orWhereNull('teachers.id')
            ->count());

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
