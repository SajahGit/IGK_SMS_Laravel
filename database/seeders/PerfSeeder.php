<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\AdmissionNumberGenerator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PerfSeeder extends Seeder
{
    private const TARGET_STUDENTS = 5000;

    private const NATIONALITIES = [
        'Sri Lankan', 'Indian', 'British', 'American', 'Emirati', 'French',
        'Kenyan', 'Chinese', 'Japanese', 'Singaporean', 'South African', 'Australian',
    ];

    private const ISSUING_COUNTRIES = [
        'Sri Lankan' => 'LK',
        'Indian' => 'IN',
        'British' => 'GB',
        'American' => 'US',
        'Emirati' => 'AE',
        'French' => 'FR',
        'Kenyan' => 'KE',
        'Chinese' => 'CN',
        'Japanese' => 'JP',
        'Singaporean' => 'SG',
        'South African' => 'ZA',
        'Australian' => 'AU',
    ];

    private const STUDENT_FIRST_NAMES = [
        'Aarav', 'Aisha', 'Amara', 'Anaya', 'Arjun', 'Ava', 'Daniel', 'Ethan',
        'Fatima', 'Hana', 'Isabella', 'Kai', 'Leila', 'Liam', 'Maya', 'Mei',
        'Noah', 'Olivia', 'Priya', 'Rohan', 'Sofia', 'Zara', 'Zoya', 'Lucas',
    ];

    private const GUARDIAN_FIRST_NAMES = [
        'Adrian', 'Amina', 'Anil', 'Carla', 'David', 'Farah', 'Grace', 'Imran',
        'Joseph', 'Meera', 'Ravi', 'Sara', 'Samira', 'Thomas', 'Yasmin', 'Arun',
        'Helen', 'Nadia', 'Kiran', 'Rafael', 'Leena', 'Omar', 'Ruth', 'Vikram',
    ];

    private const MIDDLE_NAMES = [
        'Amani', 'Anika', 'Aria', 'Elena', 'Emil', 'Farah', 'Gabriel', 'Hana',
        'Ibrahim', 'Isabel', 'Jamal', 'Kiran', 'Lina', 'Malik', 'Nadia', 'Rafael',
        'Sana', 'Tariq', 'Yara', 'Zain',
    ];

    private const FAMILY_NAMES = [
        'Abraham', 'Ali', 'Bennett', 'Chen', 'Das', 'Fernandes', 'Garcia', 'Haddad',
        'Ibrahim', 'Khan', 'Kim', 'Kumar', 'Lee', 'Martin', 'Mensah', 'Nair',
        'Patel', 'Rahman', 'Silva', 'Tan', 'Williams', 'Okafor', 'Santos', 'Yamada',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Performance data may only be seeded in local or testing environments.');
        }

        $studentCount = Student::count();
        if ($studentCount >= self::TARGET_STUDENTS) {
            $this->command?->info('Performance dataset already contains at least 5,000 students.');

            return;
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

        $year = AcademicYear::where('name', '2026-2027')->firstOrFail();
        $programs = Program::query()->whereIn('code', ['IBMYP', 'CA-IGCSE', 'IBDP'])->get()->keyBy('code');
        if ($programs->count() !== 3) {
            throw new RuntimeException('Seed the international middle, IGCSE and Diploma programmes before performance data.');
        }

        $remaining = self::TARGET_STUDENTS - $studentCount;
        $classes = SchoolClass::query()
            ->whereIn('name', array_map(fn (int $grade) => "Grade {$grade}", range(6, 12)))
            ->orderBy('name')
            ->orderBy('section')
            ->get();
        $classCounts = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $class->students()->count(),
        ]);

        $performanceClasses = $classes->reject(
            fn (SchoolClass $class) => $class->name === 'Grade 10' && $class->section === 'A'
        );
        $classSlots = $this->availableClassSlots($performanceClasses, $classCounts);
        $grades = range(6, 12);
        $gradeIndex = 0;
        while (count($classSlots) < $remaining) {
            $grade = $grades[$gradeIndex % count($grades)];
            $gradeIndex++;
            $className = "Grade {$grade}";
            $sections = SchoolClass::query()->where('name', $className)->pluck('section')->all();
            $section = collect(range(ord('A'), ord('Z')))
                ->map(fn (int $letter) => chr($letter))
                ->first(fn (string $candidate) => ! in_array($candidate, $sections, true));

            if ($section === null) {
                throw new RuntimeException("No international section names remain for {$className}.");
            }

            $programCode = match (true) {
                $grade >= 6 && $grade <= 8 => 'IBMYP',
                $grade >= 9 && $grade <= 10 => 'CA-IGCSE',
                default => 'IBDP',
            };
            $class = SchoolClass::firstOrCreate(
                ['name' => $className, 'section' => $section],
                [
                    'capacity' => 40,
                    'program_id' => $programs[$programCode]->id,
                    'academic_year_id' => $year->id,
                ]
            );
            $classes->push($class);
            $classCounts[$class->id] = $class->students()->count();
            $classSlots = array_merge($classSlots, array_fill(
                0,
                max(0, (int) $class->capacity - $classCounts[$class->id]),
                $class->id
            ));
        }

        $classSlots = array_slice($classSlots, 0, $remaining);
        $classesById = $classes->keyBy('id');
        $admissionPrefixes = [];
        $admissionSequences = [];
        foreach ($classes->pluck('program_id')->unique() as $programId) {
            $admissionPrefixes[$programId] = AdmissionNumberGenerator::prefix((int) $programId, $year->id);
            $admissionSequences[$programId] = Student::query()
                ->where('admission_no', 'like', $admissionPrefixes[$programId].'%')
                ->pluck('admission_no')
                ->map(fn (string $number) => (int) substr($number, strlen($admissionPrefixes[$programId])))
                ->max() ?? 0;
        }

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
                    'name' => $this->studentName($number),
                    'email' => $studentEmail,
                    'password' => $secretHash,
                    'role' => 'student',
                    'is_active' => true,
                    'must_change_password' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $userRows[] = [
                    'name' => $this->guardianName($number),
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
                $classesById,
                $year,
                $admissionPrefixes,
                &$admissionSequences,
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
                    $nationality = self::NATIONALITIES[($number - 1) % count(self::NATIONALITIES)];
                    $guardianRows[] = [
                        'user_id' => $users[$guardianEmail]->id,
                        'phone' => $this->samplePhone($number, $nationality),
                        'nationality' => $nationality,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                    $class = $classesById[$classSlots[$created + $offset]];
                    $programId = (int) $class->program_id;
                    $admissionSequences[$programId]++;
                    $studentRows[] = [
                        'user_id' => $users[$studentEmail]->id,
                        'class_id' => $class->id,
                        'branch_id' => $class->branch_id,
                        'program_id' => $programId,
                        'academic_year_id' => $year->id,
                        'guardian_id' => null,
                        'admission_no' => $admissionPrefixes[$programId].str_pad((string) $admissionSequences[$programId], 4, '0', STR_PAD_LEFT),
                        'admission_date' => '2026-09-01',
                        'dob' => sprintf('%04d-05-%02d', 2021 - (int) substr($class->name, 6), ($number % 27) + 1),
                        'gender' => $number % 2 === 0 ? 'female' : 'male',
                        'status' => 'active',
                        'nationality' => $nationality,
                        'document_type' => 'passport',
                        'issuing_country' => self::ISSUING_COUNTRIES[$nationality],
                        'document_number' => 'P'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
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

    /**
     * @param  EloquentCollection<int, SchoolClass>  $classes
     * @param  Collection<int, int>  $classCounts
     * @return list<int>
     */
    private function availableClassSlots(EloquentCollection $classes, Collection $classCounts): array
    {
        $slots = [];
        foreach ($classes as $class) {
            $available = max(0, (int) $class->capacity - (int) $classCounts[$class->id]);
            if ($class->program_id && $available > 0) {
                $slots = array_merge($slots, array_fill(0, $available, $class->id));
            }
        }

        return $slots;
    }

    private function studentName(int $number): string
    {
        return $this->internationalName($number, self::STUDENT_FIRST_NAMES);
    }

    private function guardianName(int $number): string
    {
        return $this->internationalName($number, self::GUARDIAN_FIRST_NAMES);
    }

    private function samplePhone(int $number, string $nationality): string
    {
        $threeDigitSuffix = sprintf('%03d', $number % 1000);

        return match ($nationality) {
            'Sri Lankan' => '+94770000'.$threeDigitSuffix,
            'Indian' => '+919860000'.$threeDigitSuffix,
            'British' => '+447400000'.$threeDigitSuffix,
            'American' => '+1202555'.sprintf('%04d', 100 + ($number % 100)),
            'Emirati' => '+971501234'.$threeDigitSuffix,
            'French' => '+33612000'.$threeDigitSuffix,
            'Kenyan' => '+254701234'.$threeDigitSuffix,
            'Chinese' => '+861380000'.sprintf('%04d', $number % 10000),
            'Japanese' => '+81801234'.sprintf('%04d', $number % 10000),
            'Singaporean' => '+658100'.sprintf('%04d', $number % 10000),
            'South African' => '+27711234'.$threeDigitSuffix,
            'Australian' => '+6141234'.sprintf('%04d', $number % 10000),
            default => throw new RuntimeException("No demo phone format is defined for {$nationality}."),
        };
    }

    /** @param list<string> $firstNames */
    private function internationalName(int $number, array $firstNames): string
    {
        $firstIndex = $number % count($firstNames);
        $middleIndex = intdiv($number, count($firstNames)) % count(self::MIDDLE_NAMES);
        $familyIndex = intdiv($number, count($firstNames) * count(self::MIDDLE_NAMES)) % count(self::FAMILY_NAMES);

        return implode(' ', [
            $firstNames[$firstIndex],
            self::MIDDLE_NAMES[$middleIndex],
            self::FAMILY_NAMES[$familyIndex],
        ]);
    }
}
