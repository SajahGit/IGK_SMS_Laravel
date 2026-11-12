<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\AdmissionNumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PerfSeeder extends Seeder
{
    private const TARGET_STUDENTS = 5000;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Performance data may only be seeded outside production.');
        }

        $this->call(DemoDataSeeder::class);

        $studentCount = Student::count();
        if ($studentCount >= self::TARGET_STUDENTS) {
            $this->command?->info('Performance dataset already contains at least 5,000 students.');

            return;
        }

        $lastUsedNumber = User::query()
            ->where(function ($query) {
                $query->where('email', 'like', 'perf.student.%@school.test')
                    ->orWhere('email', 'like', 'perf.guardian.%@school.test');
            })
            ->pluck('email')
            ->map(function (string $email): int {
                return preg_match('/^perf\.(?:student|guardian)\.(\d+)@school\.test$/', $email, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;
        $numberOffset = max($studentCount, $lastUsedNumber);

        $program = Program::where('code', 'BSC')->firstOrFail();
        $year = AcademicYear::where('name', '2026-2027')->firstOrFail();
        $prefix = AdmissionNumberGenerator::prefix($program->id, $year->id);
        $admissionNumbers = Student::where('admission_no', 'like', $prefix.'%')->pluck('admission_no');
        $sequence = $admissionNumbers
            ->map(fn (string $number) => (int) substr($number, strlen($prefix)))
            ->max() ?? 0;
        $remaining = self::TARGET_STUDENTS - $studentCount;

        $classes = SchoolClass::where('name', 'Performance Cohort')
            ->orderBy('section')
            ->get();
        $classCounts = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $class->students()->count(),
        ]);
        $nextSection = $classes->count() + 1;
        while (($classCounts->sum(fn (int $count) => 40 - $count)) < $remaining) {
            $section = sprintf('%03d', $nextSection++);
            $class = SchoolClass::firstOrCreate(
                ['name' => 'Performance Cohort', 'section' => $section],
                [
                    'capacity' => 40,
                    'program_id' => $program->id,
                    'academic_year_id' => $year->id,
                ]
            );
            $classes->push($class);
            $classCounts[$class->id] = $class->students()->count();
        }

        $classSlots = [];
        foreach ($classes as $class) {
            for ($slot = $classCounts[$class->id]; $slot < 40; $slot++) {
                $classSlots[] = $class->id;
            }
        }
        $classSlots = array_slice($classSlots, 0, $remaining);

        $secretHash = Hash::make('password');
        $timestamp = now();
        $created = 0;
        while ($created < $remaining) {
            $batchSize = min(50, $remaining - $created);
            $userRows = [];
            $studentEmails = [];
            $guardianEmails = [];

            for ($offset = 0; $offset < $batchSize; $offset++) {
                $number = $numberOffset + $created + $offset + 1;
                $studentEmail = sprintf('perf.student.%05d@school.test', $number);
                $guardianEmail = sprintf('perf.guardian.%05d@school.test', $number);
                $studentEmails[] = $studentEmail;
                $guardianEmails[] = $guardianEmail;
                $userRows[] = [
                    'name' => sprintf('Performance Student %05d', $number),
                    'email' => $studentEmail,
                    'password' => $secretHash,
                    'role' => 'student',
                    'is_active' => true,
                    'must_change_password' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $userRows[] = [
                    'name' => sprintf('Performance Guardian %05d', $number),
                    'email' => $guardianEmail,
                    'password' => $secretHash,
                    'role' => 'parent',
                    'is_active' => true,
                    'must_change_password' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            DB::transaction(function () use (
                $userRows,
                $studentEmails,
                $guardianEmails,
                $classSlots,
                $created,
                $numberOffset,
                $program,
                $year,
                $prefix,
                &$sequence,
                $timestamp
            ) {
                DB::table('users')->insert($userRows);
                $users = DB::table('users')
                    ->whereIn('email', array_merge($studentEmails, $guardianEmails))
                    ->get(['id', 'email'])
                    ->keyBy('email');
                $guardianRows = [];
                $studentRows = [];

                foreach ($studentEmails as $offset => $studentEmail) {
                    $number = $numberOffset + $created + $offset + 1;
                    $guardianEmail = $guardianEmails[$offset];
                    $guardianRows[] = [
                        'user_id' => $users[$guardianEmail]->id,
                        'phone' => sprintf('555%07d', $number),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                    $sequence++;
                    $studentRows[] = [
                        'user_id' => $users[$studentEmail]->id,
                        'class_id' => $classSlots[$created + $offset],
                        'program_id' => $program->id,
                        'academic_year_id' => $year->id,
                        'guardian_id' => null,
                        'admission_no' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                        'admission_date' => '2026-09-01',
                        'dob' => sprintf('%04d-05-%02d', 2009 + ($number % 2), ($number % 27) + 1),
                        'gender' => $number % 2 === 0 ? 'female' : 'male',
                        'status' => 'active',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                DB::table('guardians')->insert($guardianRows);
                $guardianIds = DB::table('guardians')
                    ->whereIn('user_id', $users->whereIn('email', $guardianEmails)->pluck('id'))
                    ->get(['id', 'user_id'])
                    ->keyBy('user_id');
                foreach ($studentRows as $offset => &$studentRow) {
                    $guardianUserId = $users[$guardianEmails[$offset]]->id;
                    $studentRow['guardian_id'] = $guardianIds[$guardianUserId]->id;
                }
                unset($studentRow);
                DB::table('students')->insert($studentRows);
                $studentIds = DB::table('students')
                    ->whereIn('user_id', $users->whereIn('email', $studentEmails)->pluck('id'))
                    ->get(['id', 'user_id'])
                    ->keyBy('user_id');
                $pivotRows = [];
                foreach ($studentEmails as $offset => $studentEmail) {
                    $studentUserId = $users[$studentEmail]->id;
                    $guardianUserId = $users[$guardianEmails[$offset]]->id;
                    $guardianId = $guardianIds[$guardianUserId]->id;
                    $pivotRows[] = [
                        'guardian_id' => $guardianId,
                        'student_id' => $studentIds[$studentUserId]->id,
                        'is_primary' => true,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                DB::table('guardian_student')->insert($pivotRows);
            });

            $created += $batchSize;
        }

        $this->command?->info('Seeded '.Student::count().' students for performance testing.');
    }
}
