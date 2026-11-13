<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Exam;
use App\Models\FeeStructure;
use App\Models\GradeScale;
use App\Models\Guardian;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\SchoolClass;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentTransportAssignment;
use App\Models\Subject;
use App\Models\Supplier;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportVehicle;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\HrPayrollService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ProcurementService;
use App\Services\ResultPublicationService;
use App\Services\TermResultService;
use App\Services\TransportService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data may only be seeded in local or testing environments.');
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(SettingSeeder::class);
        if (! GradeScale::query()->where('is_default', true)->exists()) {
            $this->call(GradeScaleSeeder::class);
        }

        $this->call(SuperAdminSeeder::class);
        User::firstOrCreate(
            ['email' => 'admin@school.test'],
            [
                'name' => 'International School Administrator',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->call(AcademicStructureSeeder::class);
        $this->command?->info('Academic structure ready. Preparing international-school sample data...');
        $this->command?->info('Academic structure ready; preparing demo staff and students...');

        $year = AcademicYear::firstOrCreate(
            ['name' => '2026-2027'],
            ['start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]
        );
        $term = Term::firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Term 1'],
            ['sequence' => 1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-15', 'is_current' => true]
        );
        Term::firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Term 2'],
            ['sequence' => 2, 'start_date' => '2027-01-16', 'end_date' => '2027-06-30', 'is_current' => false]
        );
        AcademicYear::where('is_current', true)->whereKeyNot($year->id)->update(['is_current' => false]);
        $year->update(['is_current' => true]);
        Term::where('is_current', true)->whereKeyNot($term->id)->update(['is_current' => false]);
        $term->update(['is_current' => true]);

        $programCodes = [
            'IBPYP', 'CAMPRIM', 'AMER-ELEM', 'IBMYP', 'CAMLS', 'AMER-MS',
            'CA-IGCSE', 'ED-IGCSE', 'US-HS', 'IBDP', 'CAM-AL', 'ED-AL',
        ];
        $programs = Program::query()->whereIn('code', $programCodes)->get()->keyBy('code');
        $subjectCodes = [
            'ENP', 'NUM', 'PLS', 'SEN', 'WLD', 'ENG', 'LIT', 'MTH', 'FMA',
            'BIO', 'CHE', 'PHY', 'SCI', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC',
            'PSY', 'GPR', 'FRE', 'SPA', 'MAN', 'ARA', 'TAM', 'ART', 'MUS',
            'PE', 'ICT', 'CS', 'ENV', 'TOK', 'EE', 'CAS', 'APCALC', 'APBIO',
            'APCHEM', 'APPHYS', 'APCS', 'APUSH', 'APENG', 'APECON',
            'SIN', 'HIN', 'JPN', 'SWA', 'MAL', 'ZUL', 'AFR',
        ];
        $subjects = Subject::query()
            ->whereIn('code', $subjectCodes)
            ->get()
            ->sortBy(fn (Subject $subject) => array_search($subject->code, $subjectCodes, true))
            ->values();

        $classLevels = [
            ['Nursery', 'A', 'IBPYP'],
            ['Pre-K', 'A', 'IBPYP'],
            ['Reception', 'A', 'IBPYP'],
        ];
        foreach (range(1, 5) as $grade) {
            foreach (['A' => 'IBPYP', 'B' => 'CAMPRIM', 'C' => 'AMER-ELEM'] as $section => $programCode) {
                $classLevels[] = ["Grade {$grade}", $section, $programCode];
            }
        }
        foreach (range(6, 8) as $grade) {
            foreach (['A' => 'IBMYP', 'B' => 'CAMLS', 'C' => 'AMER-MS'] as $section => $programCode) {
                $classLevels[] = ["Grade {$grade}", $section, $programCode];
            }
        }
        foreach (range(1, 6) as $yearNumber) {
            $classLevels[] = ["Year {$yearNumber}", 'A', 'CAMPRIM'];
        }
        foreach (range(7, 9) as $yearNumber) {
            $classLevels[] = ["Year {$yearNumber}", 'A', 'CAMLS'];
        }
        foreach (range(9, 10) as $grade) {
            foreach (['A' => 'IBMYP', 'B' => 'CA-IGCSE', 'C' => 'ED-IGCSE', 'D' => 'US-HS'] as $section => $programCode) {
                $classLevels[] = ["Grade {$grade}", $section, $programCode];
            }
        }
        foreach (range(11, 12) as $grade) {
            foreach (['A' => 'IBDP', 'B' => 'CAM-AL', 'C' => 'ED-AL', 'D' => 'US-HS'] as $section => $programCode) {
                $classLevels[] = ["Grade {$grade}", $section, $programCode];
            }
        }
        $classes = collect($classLevels)->map(function (array $level) use ($programs, $year) {
            [$name, $section, $programCode] = $level;
            $attributes = [
                'capacity' => 40,
                'program_id' => $programs[$programCode]->id,
                'academic_year_id' => $year->id,
            ];
            $class = SchoolClass::firstOrCreate(
                ['name' => $name, 'section' => $section],
                $attributes
            );
            $hasNonDemoStudents = $class->students()
                ->whereHas('user', fn ($query) => $query
                    ->where('email', 'not like', 'demo.student.%@school.test')
                    ->where('email', '!=', 'student@school.test'))
                ->exists();
            $updates = [];
            if (! $hasNonDemoStudents && $class->capacity !== $attributes['capacity']) {
                $updates['capacity'] = $attributes['capacity'];
            }
            if ($class->program_id === null) {
                $updates['program_id'] = $attributes['program_id'];
            }
            if (! $class->academic_year_id) {
                $updates['academic_year_id'] = $attributes['academic_year_id'];
            }
            if ($updates !== []) {
                $class->update($updates);
            }

            return $class;
        });

        $departments = Department::query()
            ->whereIn('code', ['EARLY', 'LANG', 'MATH', 'SCI', 'HUM', 'ARTS', 'TECH', 'WELL'])
            ->orderBy('id')
            ->get();
        if ($departments->isEmpty()) {
            throw new RuntimeException('International academic departments must be seeded before demo teachers.');
        }
        $this->seedTeachers($departments);
        $this->ensureWellbeingTeacher($departments);
        $teachers = Teacher::query()->orderBy('id')->get();
        $admin = User::where('email', 'admin@school.test')->firstOrFail();
        $this->seedStudents($classes, $programs, $year, $admin);
        $this->seedOfferings($classes, $subjects, $teachers, $term);
        $this->command?->info('Student enrollments ready; generating attendance and HR data...');
        $this->seedStudentAttendance($classes, $term, $teachers, $admin);
        $this->seedStaffAttendance($teachers, $admin);
        $this->seedHrPayroll($teachers, $admin);
        $this->command?->info('Attendance and HR data ready; generating exams, results, and invoices...');
        $this->seedExamsAndTermResults($classes, $term, $admin);
        $this->seedInvoices($classes, $programs, $year, $term, $admin);
        $this->command?->info('Academic and finance data ready; generating support-module samples...');
        $this->seedInventory($admin);
        $this->seedProcurement($admin);
        $this->seedTransport($year);
        $this->call(HostelCafeteriaDemoSeeder::class);
        $this->call(LocalDemoCredentialsSeeder::class);
        $this->call(TeacherSubjectAssignmentSeeder::class);
        $this->call(LmsDemoSeeder::class);
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
            default => '+94770000'.$threeDigitSuffix,
        };
    }

    /** @return array{document_type: string, issuing_country: string, document_number: string} */
    private function sampleDocument(int $number, string $nationality): array
    {
        $country = [
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
        ][$nationality];

        if ($country === 'AU' || $number % 2 === 0) {
            return [
                'document_type' => 'passport',
                'issuing_country' => $country,
                'document_number' => 'P'.str_pad((string) $number, 7, '0', STR_PAD_LEFT),
            ];
        }

        $nationalId = match ($country) {
            'LK' => sprintf('%09d', 900000000 + $number).'V',
            'IN' => sprintf('%012d', 200000000000 + $number),
            'GB' => 'AB'.sprintf('%06d', $number).'A',
            'US' => sprintf('%09d', 900000000 + $number),
            'AE' => '7841990'.sprintf('%08d', $number),
            'FR' => 'AB'.sprintf('%010d', $number),
            'KE' => sprintf('%08d', 10000000 + $number),
            'CN' => '110101199001'.sprintf('%03d', $number),
            'JP' => sprintf('%012d', 100000000000 + $number),
            'SG' => 'S'.sprintf('%07d', $number).'A',
            'ZA' => '900101'.sprintf('%05d', $number).'08',
            default => throw new RuntimeException("No demo identity format is defined for {$country}."),
        };

        return [
            'document_type' => 'national_id',
            'issuing_country' => $country,
            'document_number' => $nationalId,
        ];
    }

    /** @param Collection<int, Department> $departments */
    private function seedTeachers(Collection $departments): void
    {
        $firstNames = ['Aisha', 'Daniel', 'Elena', 'Fatima', 'Gabriel', 'Hana', 'Ibrahim', 'Isabella', 'Kai', 'Leila', 'Mateo', 'Mei'];
        $lastNames = ['Abraham', 'Chen', 'Das', 'Fernandes', 'Haddad', 'Khan', 'Kumar', 'Lee', 'Martin', 'Nair', 'Patel', 'Tan'];
        $next = Teacher::count() + 1;

        while (Teacher::count() < 12) {
            $number = $next++;
            $name = $this->sampleName($number, $firstNames, $lastNames);
            $user = User::firstOrCreate(
                ['email' => sprintf('demo.staff.%02d@school.test', $number)],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => 'teacher',
                    'is_active' => true,
                    'must_change_password' => false,
                ]
            );
            if ($user->name !== $name) {
                $user->update(['name' => $name]);
            }
            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $departments[($number - 1) % $departments->count()]->id,
                    'phone' => $this->samplePhone($number, 'Sri Lankan'),
                    'status' => 'active',
                ]
            );
            $teacher->update(['phone' => $this->samplePhone($number, 'Sri Lankan')]);
        }

        User::query()->where('email', 'like', 'demo.staff.%@school.test')->get()->each(function (User $user) use ($firstNames, $lastNames): void {
            if (preg_match('/demo\.staff\.(\d+)@school\.test/', $user->email, $matches)) {
                $user->update(['name' => $this->sampleName((int) $matches[1], $firstNames, $lastNames)]);
            }
        });
    }

    /** @param Collection<int, Department> $departments */
    private function ensureWellbeingTeacher(Collection $departments): void
    {
        $department = $departments->firstWhere('code', 'WELL');
        if (! $department) {
            throw new RuntimeException('The Physical Education and Wellbeing department must be seeded before demo teachers.');
        }

        if (Teacher::query()->where('department_id', $department->id)->exists()) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'demo.staff.wellbeing@school.test'],
            [
                'name' => 'Rafael Mensah',
                'password' => 'password',
                'role' => 'teacher',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $teacher = Teacher::firstOrCreate(
            ['user_id' => $user->id],
            [
                'department_id' => $department->id,
                'phone' => $this->samplePhone(13, 'Sri Lankan'),
                'status' => 'active',
            ]
        );
        if ($teacher->department_id !== $department->id) {
            $teacher->update(['department_id' => $department->id]);
        }
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  Collection<int, Program>  $programs
     */
    private function seedStudents(Collection $classes, Collection $programs, AcademicYear $year, User $admin): void
    {
        $studentFirstNames = ['Aarav', 'Aisha', 'Amara', 'Anaya', 'Arjun', 'Ava', 'Daniel', 'Ethan', 'Fatima', 'Hana', 'Isabella', 'Kai', 'Leila', 'Liam', 'Maya', 'Mei', 'Noah', 'Olivia', 'Priya', 'Rohan', 'Sofia', 'Zara', 'Zoya', 'Lucas'];
        $guardianFirstNames = ['Adrian', 'Amina', 'Anil', 'Carla', 'David', 'Farah', 'Grace', 'Imran', 'Joseph', 'Meera', 'Ravi', 'Sara', 'Samira', 'Thomas', 'Yasmin', 'Arun', 'Helen', 'Nadia', 'Kiran', 'Rafael', 'Leena', 'Omar', 'Ruth', 'Vikram'];
        $familyNames = ['Abraham', 'Ali', 'Bennett', 'Chen', 'Das', 'Fernandes', 'Garcia', 'Haddad', 'Ibrahim', 'Khan', 'Kim', 'Kumar', 'Lee', 'Martin', 'Mensah', 'Nair', 'Patel', 'Rahman', 'Silva', 'Tan'];
        $nationalities = ['Sri Lankan', 'Indian', 'British', 'American', 'Emirati', 'French', 'Kenyan', 'Chinese', 'Japanese', 'Singaporean', 'South African', 'Australian'];
        $classCounts = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $class->students()->count(),
        ]);
        $demoCounts = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $class->students()->whereHas('user', fn ($query) => $query
                ->where('email', 'like', 'demo.student.%@school.test')
                ->orWhere('email', 'student@school.test'))->count(),
        ]);

        Student::query()
            ->select(['id', 'class_id', 'program_id', 'academic_year_id'])
            ->chunkById(250, function (EloquentCollection $students) use ($classes): void {
                foreach ($students as $student) {
                    $class = $classes->firstWhere('id', $student->class_id);
                    if ($class && (! $student->program_id || ! $student->academic_year_id)) {
                        $student->update([
                            'program_id' => $student->program_id ?: $class->program_id,
                            'academic_year_id' => $student->academic_year_id ?: $class->academic_year_id,
                        ]);
                    }
                }
            });
        User::query()->where('email', 'like', 'demo.student.%@school.test')->get()->each(function (User $user) use ($studentFirstNames, $familyNames): void {
            if (preg_match('/demo\.student\.(\d+)@school\.test/', $user->email, $matches)) {
                $user->update(['name' => $this->sampleName((int) $matches[1], $studentFirstNames, $familyNames)]);
            }
        });
        User::query()->where('email', 'like', 'demo.guardian.%@school.test')->get()->each(function (User $user) use ($guardianFirstNames, $familyNames, $nationalities): void {
            if (preg_match('/demo\.guardian\.(\d+)@school\.test/', $user->email, $matches)) {
                $number = (int) $matches[1];
                $user->update(['name' => $this->sampleName($number, $guardianFirstNames, $familyNames)]);
                Guardian::query()->where('user_id', $user->id)->update([
                    'phone' => $this->samplePhone($number, $nationalities[($number - 1) % count($nationalities)]),
                    'nationality' => $nationalities[($number - 1) % count($nationalities)],
                ]);
            }
        });
        $legacyDemoStudentEmails = ['student@school.test'];
        foreach (range(2, 11) as $studentNumber) {
            $legacyDemoStudentEmails[] = "student{$studentNumber}@school.test";
        }
        Student::query()
            ->whereHas('user', fn ($query) => $query
                ->where('email', 'like', 'demo.student.%@school.test')
                ->orWhereIn('email', $legacyDemoStudentEmails))
            ->with('user')
            ->get()
            ->each(function (Student $student): void {
                if ($student->document_number !== null) {
                    return;
                }

                $number = preg_match('/demo\.student\.(\d+)@school\.test/', $student->user->email, $matches) === 1
                    ? (int) $matches[1]
                    : 10000 + (preg_match('/student(\d*)@school\.test/', $student->user->email, $matches) === 1
                        ? max(1, (int) $matches[1])
                        : 1);
                $nationalities = ['Sri Lankan', 'Indian', 'British', 'American', 'Emirati', 'French', 'Kenyan', 'Chinese', 'Japanese', 'Singaporean', 'South African', 'Australian'];
                $student->update($this->sampleDocument($number, $nationalities[($number - 1) % count($nationalities)]));
            });
        Student::query()
            ->whereNotNull('guardian_id')
            ->select(['id', 'guardian_id'])
            ->chunkById(500, function (EloquentCollection $students): void {
                $guardianLinks = $students->map(fn (Student $student) => [
                    'guardian_id' => $student->guardian_id,
                    'student_id' => $student->id,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('guardian_student')->insertOrIgnore($guardianLinks->all());
            });

        $basePerClass = intdiv(240, max(1, $classes->count()));
        $additionalStudents = 240 % max(1, $classes->count());
        $classTargets = $classes->values()->mapWithKeys(fn (SchoolClass $class, int $index) => [
            $class->id => $basePerClass + ($index < $additionalStudents ? 1 : 0),
        ]);
        $next = 1;
        $demoStudentCount = (int) $demoCounts->sum();
        while ($demoStudentCount < 240) {
            $class = $classes->first(fn (SchoolClass $candidate) => $demoCounts[$candidate->id] < $classTargets[$candidate->id]
                && $classCounts[$candidate->id] < (int) $candidate->capacity);
            $class ??= $classes->first(fn (SchoolClass $candidate) => $classCounts[$candidate->id] < (int) $candidate->capacity);
            if (! $class) {
                throw new RuntimeException('The international demo dataset needs 240 available student places across its grade sections.');
            }

            do {
                $number = $next++;
                $studentEmail = sprintf('demo.student.%03d@school.test', $number);
                $guardianEmail = sprintf('demo.guardian.%03d@school.test', $number);
            } while (User::query()->where('email', $studentEmail)->exists());

            $guardianName = $this->sampleName($number, $guardianFirstNames, $familyNames);
            $studentName = $this->sampleName($number, $studentFirstNames, $familyNames);
            $nationality = $nationalities[($number - 1) % count($nationalities)];
            $guardianUser = User::firstOrCreate(['email' => $guardianEmail], [
                'name' => $guardianName,
                'password' => 'password',
                'role' => 'parent',
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $guardian = Guardian::firstOrCreate(
                ['user_id' => $guardianUser->id],
                [
                    'phone' => $this->samplePhone($number, $nationality),
                    'nationality' => $nationality,
                ]
            );
            $programCode = $class->program_id ? null : $this->programCodeForClass($class);
            if ($class->program_id === null && $programCode === null) {
                throw new RuntimeException("No curriculum track is defined for {$class->full_name}.");
            }
            if ($programCode !== null && ! isset($programs[$programCode])) {
                throw new RuntimeException("The demo curriculum program {$programCode} is missing.");
            }

            EnrollmentService::enrollStudent([
                'name' => $studentName,
                'email' => $studentEmail,
                'password' => 'password',
                'class_id' => $class->id,
                'program_id' => $class->program_id ?: $programs[$programCode]->id,
                'academic_year_id' => $class->academic_year_id ?: $year->id,
                'primary_guardian_id' => $guardian->id,
                'gender' => $number % 2 === 0 ? 'female' : 'male',
                'nationality' => $nationality,
                ...$this->sampleDocument($number, $nationality),
                'dob' => sprintf('%04d-05-%02d', $this->birthYearForClass($class), ($number % 27) + 1),
                'admission_date' => '2026-09-01',
            ]);

            $classCounts->put($class->id, $classCounts[$class->id] + 1);
            $demoCounts->put($class->id, $demoCounts[$class->id] + 1);
            $demoStudentCount++;
        }

        if ($admin->exists) {
            $this->command?->info('Seeded '.Student::count().' students.');
        }
    }

    private function birthYearForClass(SchoolClass $class): int
    {
        return match ($class->name) {
            'Nursery' => 2023,
            'Pre-K' => 2022,
            'Reception' => 2021,
            default => preg_match('/^(?:Grade|Year) (\d+)$/', $class->name, $matches) === 1
                ? 2021 - (int) $matches[1]
                : 2010,
        };
    }

    private function programCodeForClass(SchoolClass $class): ?string
    {
        if (in_array($class->name, ['Nursery', 'Pre-K', 'Reception'], true)) {
            return 'IBPYP';
        }

        if (preg_match('/^Grade (\d+)$/', $class->name, $matches) === 1) {
            $grade = (int) $matches[1];
            $sectionPrograms = match (true) {
                $grade >= 1 && $grade <= 5 => ['A' => 'IBPYP', 'B' => 'CAMPRIM', 'C' => 'AMER-ELEM'],
                $grade >= 6 && $grade <= 8 => ['A' => 'IBMYP', 'B' => 'CAMLS', 'C' => 'AMER-MS'],
                $grade >= 9 && $grade <= 10 => ['A' => 'IBMYP', 'B' => 'CA-IGCSE', 'C' => 'ED-IGCSE', 'D' => 'US-HS'],
                $grade >= 11 && $grade <= 12 => ['A' => 'IBDP', 'B' => 'CAM-AL', 'C' => 'ED-AL', 'D' => 'US-HS'],
                default => [],
            };

            return $sectionPrograms[$class->section] ?? null;
        }

        if (preg_match('/^Year (\d+)$/', $class->name, $matches) === 1) {
            return (int) $matches[1] <= 6 ? 'CAMPRIM' : 'CAMLS';
        }

        return null;
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Teacher>  $teachers
     */
    private function seedOfferings(Collection $classes, EloquentCollection $subjects, EloquentCollection $teachers, Term $term): void
    {
        $programCodes = Program::query()->whereIn('id', $classes->pluck('program_id')->unique())->pluck('code', 'id');
        foreach ($classes as $class) {
            $programCode = $class->program_id
                ? ($programCodes[$class->program_id] ?? '')
                : ($this->programCodeForClass($class) ?? '');
            $subjectCodes = match ($programCode) {
                'IBPYP' => ['ENP', 'NUM', 'PLS', 'SEN', 'WLD', 'ENG', 'MTH', 'SCI', 'GPR', 'ART', 'MUS', 'PE'],
                'CAMPRIM' => ['ENG', 'MTH', 'SCI', 'HIS', 'GEO', 'ICT', 'FRE', 'ART', 'MUS', 'PE', 'GPR'],
                'AMER-ELEM', 'AMER-MS' => ['ENG', 'MTH', 'SCI', 'HIS', 'GEO', 'ICT', 'ART', 'MUS', 'PE', 'CS'],
                'IBMYP' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'FRE', 'GPR', 'ART', 'MUS', 'PE', 'CS'],
                'CAMLS' => ['ENG', 'MTH', 'SCI', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ICT', 'FRE', 'GPR', 'ART', 'PE'],
                'CA-IGCSE', 'ED-IGCSE' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'CS', 'FRE'],
                'IBDP' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'PSY', 'FRE', 'MAN', 'ART', 'TOK', 'EE', 'CAS'],
                'CAM-AL', 'ED-AL' => ['ENG', 'LIT', 'MTH', 'FMA', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'PSY', 'CS'],
                'US-HS' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'CS', 'APCALC', 'APBIO', 'APCHEM', 'APPHYS', 'APCS', 'APUSH', 'APENG', 'APECON'],
                default => [],
            };
            $subjectCodes = array_values(array_unique(array_merge(
                $subjectCodes,
                ['SIN', 'HIN', 'JPN', 'SWA', 'MAL', 'ZUL', 'AFR', 'TAM', 'MAN', 'ARA', 'FRE', 'SPA']
            )));
            foreach ($subjectCodes as $index => $subjectCode) {
                $subject = $subjects->firstWhere('code', $subjectCode);
                if (! $subject) {
                    continue;
                }
                ClassSubject::firstOrCreate(
                    ['class_id' => $class->id, 'subject_id' => $subject->id, 'term_id' => $term->id],
                    ['teacher_id' => $teachers[$index % $teachers->count()]->id]
                );
            }

            Student::where('class_id', $class->id)->each(
                fn (Student $student) => EnrollmentService::syncClassSubjects($student->load('class'))
            );
        }

        $this->seedSampleTimetable($classes, $term);
    }

    /** @param Collection<int, SchoolClass> $classes */
    private function seedSampleTimetable(Collection $classes, Term $term): void
    {
        foreach ($classes as $class) {
            $offerings = ClassSubject::query()
                ->where('class_id', $class->id)
                ->where('term_id', $term->id)
                ->orderBy('id')
                ->take(10)
                ->get();

            foreach (range(1, 5) as $day) {
                foreach ($offerings->slice(($day - 1) * 2, 2)->values() as $period => $offering) {
                    $start = $period === 0 ? '08:00:00' : '08:45:00';
                    $end = date('H:i:s', strtotime($start.' +40 minutes'));
                    TimetableSlot::firstOrCreate(
                        [
                            'class_subject_id' => $offering->id,
                            'day_of_week' => $day,
                            'start_time' => $start,
                            'end_time' => $end,
                        ],
                        ['room' => 'Learning Centre '.$class->id]
                    );
                }
            }
        }
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  EloquentCollection<int, Teacher>  $teachers
     */
    private function seedStudentAttendance(Collection $classes, Term $term, EloquentCollection $teachers, User $admin): void
    {
        $days = collect(range(0, 60))
            ->map(fn (int $offset) => today()->subDays($offset))
            ->filter(fn ($date) => $date->isWeekday())
            ->take(30)
            ->values()
            ->reverse()
            ->values();

        foreach ($classes as $class) {
            $offering = ClassSubject::where('class_id', $class->id)
                ->where('term_id', $term->id)
                ->orderBy('id')
                ->firstOrFail();

            foreach ($days as $date) {
                $session = AttendanceSession::query()
                    ->where('class_id', $class->id)
                    ->where('class_subject_id', $offering->id)
                    ->whereDate('date', $date)
                    ->where('start_time', '08:00:00')
                    ->first();
                if (! $session) {
                    $session = AttendanceSession::create([
                        'class_id' => $class->id,
                        'class_subject_id' => $offering->id,
                        'term_id' => $term->id,
                        'date' => $date->toDateString(),
                        'start_time' => '08:00:00',
                        'session_label' => 'Daily attendance',
                        'taken_by' => $teachers->first()->user_id,
                    ]);
                }
                $students = Student::where('class_id', $class->id)->get(['id']);
                $existing = Attendance::where('attendance_session_id', $session->id)->pluck('student_id')->all();
                $existingIds = array_fill_keys($existing, true);
                $legacy = Attendance::whereNull('attendance_session_id')
                    ->where('date', $date->toDateString())
                    ->whereIn('student_id', $students->pluck('id'))
                    ->get()
                    ->keyBy('student_id');
                $rows = [];

                foreach ($students as $student) {
                    if (isset($existingIds[$student->id])) {
                        continue;
                    }

                    if (isset($legacy[$student->id])) {
                        $legacy[$student->id]->update([
                            'attendance_session_id' => $session->id,
                            'marked_by' => $admin->id,
                            'marked_at' => now(),
                        ]);

                        continue;
                    }

                    $rows[] = [
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'attendance_session_id' => $session->id,
                        'date' => $date->toDateString(),
                        'status' => ($student->id + $date->day) % 17 === 0 ? 'absent' : 'present',
                        'remarks' => null,
                        'marked_by' => $admin->id,
                        'marked_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('attendance')->insertOrIgnore($chunk);
                }
            }
        }
    }

    /** @param EloquentCollection<int, Teacher> $teachers */
    private function seedStaffAttendance(EloquentCollection $teachers, User $admin): void
    {
        $days = collect(range(0, 60))
            ->map(fn (int $offset) => today()->subDays($offset))
            ->filter(fn ($date) => $date->isWeekday())
            ->take(30);

        foreach ($teachers as $teacher) {
            foreach ($days as $date) {
                $attendance = StaffAttendance::query()
                    ->where('teacher_id', $teacher->id)
                    ->whereDate('attendance_date', $date)
                    ->first();
                if (! $attendance) {
                    StaffAttendance::create([
                        'teacher_id' => $teacher->id,
                        'attendance_date' => $date->toDateString(),
                        'staff_name' => $teacher->user->name,
                        'check_in' => '08:00:00',
                        'check_out' => '16:00:00',
                        'notes' => null,
                        'recorded_by' => $admin->id,
                    ]);
                }
            }
        }
    }

    /** @param Collection<int, SchoolClass> $classes */
    private function seedExamsAndTermResults(Collection $classes, Term $term, User $admin): void
    {
        foreach ($classes as $class) {
            $offerings = ClassSubject::where('class_id', $class->id)
                ->where(fn ($query) => $query->where('term_id', $term->id)->orWhereNull('term_id'))
                ->with('subject')
                ->get()
                ->groupBy('subject_id')
                ->map(fn ($matches) => $matches->firstWhere('term_id', $term->id) ?? $matches->first());

            foreach ($offerings as $offering) {
                $studentIds = DB::table('student_subject_enrollments')
                    ->join('students', 'student_subject_enrollments.student_id', '=', 'students.id')
                    ->where('student_subject_enrollments.class_subject_id', $offering->id)
                    ->where('student_subject_enrollments.status', 'enrolled')
                    ->where('students.status', 'active')
                    ->pluck('student_subject_enrollments.student_id');
                if ($studentIds->isEmpty()) {
                    continue;
                }

                $exam = Exam::firstOrCreate(
                    [
                        'name' => 'Term 1 - '.$offering->subject->code,
                        'class_id' => $class->id,
                        'subject_id' => $offering->subject_id,
                        'term_id' => $term->id,
                    ],
                    [
                        'type' => 'final',
                        'exam_date' => '2027-01-10',
                        'max_marks' => 100,
                        'weight' => 100,
                        'created_by' => $admin->id,
                    ]
                );

                $rows = $studentIds->map(fn (int $studentId) => [
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'marks' => 55 + (($studentId * 17 + $offering->subject_id * 13) % 46),
                    'is_absent' => false,
                    'is_excused' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('results')->insertOrIgnore($chunk);
                }

                if (! $exam->is_published) {
                    ResultPublicationService::publish($exam);
                }
            }
        }

        $service = app(TermResultService::class);
        foreach ($classes as $class) {
            if (DB::table('term_results')
                ->where('term_id', $term->id)
                ->where('class_id', $class->id)
                ->where('publication_status', 'published')
                ->exists()) {
                continue;
            }

            $service->generate($term, $class->id);
            $service->submitForApproval($term, $class->id, $admin);
            $service->publishClass($term, $class->id, $admin);
        }
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  Collection<int, Program>  $programs
     */
    private function seedInvoices(Collection $classes, Collection $programs, AcademicYear $year, Term $term, User $admin): void
    {
        foreach ($programs as $program) {
            $currency = config('school.program_currencies.'.$program->code, config('school.default_currency', 'LKR'));
            $amount = [
                'LKR' => '450000.00',
                'USD' => '1800.00',
                'GBP' => '1400.00',
                'EUR' => '1650.00',
                'AED' => '6600.00',
            ][$currency] ?? '450000.00';
            FeeStructure::updateOrCreate(
                [
                    'name' => 'International School Term Tuition',
                    'program_id' => $program->id,
                    'academic_year_id' => $year->id,
                    'class_id' => null,
                    'term_id' => $term->id,
                ],
                ['amount' => $amount, 'currency' => $currency, 'is_active' => true]
            );
        }

        foreach ($classes as $class) {
            $programId = $class->program_id;
            if ($programId === null) {
                $programCode = $this->programCodeForClass($class);
                if ($programCode === null || ! isset($programs[$programCode])) {
                    throw new RuntimeException("No curriculum track is defined for {$class->full_name}.");
                }
                $programId = $programs[$programCode]->id;
            }
            $feeStructureIds = FeeStructure::query()
                ->whereNull('class_id')
                ->where('program_id', $programId)
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            InvoiceService::bulkGenerateForClass(
                $class->id,
                $term->id,
                today()->toDateString(),
                today()->addDays(30)->toDateString(),
                $feeStructureIds
            );
        }

        foreach (DB::table('invoices')->where('term_id', $term->id)->orderBy('id')->get() as $invoice) {
            if (
                (float) $invoice->balance <= 0
                || (float) $invoice->amount_paid > 0
                || DB::table('payments')->where('invoice_id', $invoice->id)->exists()
            ) {
                continue;
            }

            if ($invoice->id % 10 === 0) {
                PaymentService::recordPayment($invoice->id, $invoice->balance, 'cash', 'DEMO-'.$invoice->id, today()->toDateString(), $admin->id);
            } elseif ($invoice->id % 10 === 1) {
                $amount = Money::mul($invoice->balance, '0.20');
                if (Money::comp($amount, '0.00') > 0) {
                    PaymentService::recordPayment($invoice->id, $amount, 'cash', 'DEMO-'.$invoice->id, today()->toDateString(), $admin->id);
                }
            }
        }
    }

    private function seedInventory(User $admin): void
    {
        $items = [
            ['sku' => 'DEMO-LAB-001', 'name' => 'International Science Lab Equipment Set', 'category' => 'Laboratory', 'unit' => 'kit', 'quantity' => 24, 'reorder_level' => 5, 'description' => 'Microscope slides, glassware and safety equipment for Cambridge and IB practical science classes.'],
            ['sku' => 'DEMO-STA-001', 'name' => 'Adjustable Student Desk', 'category' => 'Furniture', 'unit' => 'piece', 'quantity' => 48, 'reorder_level' => 8, 'description' => 'Height-adjustable classroom desks for Early Years through Grade 12 learning spaces.'],
            ['sku' => 'DEMO-STAT-001', 'name' => 'International Curriculum Exercise Book', 'category' => 'Stationery', 'unit' => 'book', 'quantity' => 180, 'reorder_level' => 50, 'description' => 'Ruled exercise books for English, humanities and international programme coursework.'],
            ['sku' => 'DEMO-IT-001', 'name' => 'Multilingual USB Keyboard', 'category' => 'IT Equipment', 'unit' => 'piece', 'quantity' => 12, 'reorder_level' => 4, 'description' => 'USB keyboards for the school computing and digital literacy suites.'],
            ['sku' => 'DEMO-IT-002', 'name' => 'Interactive Classroom Projector Lamp', 'category' => 'IT Equipment', 'unit' => 'piece', 'quantity' => 2, 'reorder_level' => 3, 'description' => 'Replacement lamps for interactive teaching projectors in upper-secondary classrooms.'],
        ];

        foreach ($items as $data) {
            $initialQuantity = $data['quantity'];
            unset($data['quantity']);
            $item = InventoryItem::firstOrCreate(['sku' => $data['sku']], $data + ['is_active' => true]);
            if ((int) $item->quantity_on_hand === 0 && $item->movements()->doesntExist() && $initialQuantity > 0) {
                InventoryService::recordMovement(
                    $item,
                    'receipt',
                    $initialQuantity,
                    'DEMO-OPENING',
                    'Deterministic demo opening stock',
                    $admin->id
                );
            }

        }
    }

    /** @param EloquentCollection<int, Teacher> $teachers */
    private function seedHrPayroll(EloquentCollection $teachers, User $admin): void
    {
        foreach ($teachers->values() as $index => $teacher) {
            Employee::firstOrCreate(
                ['employee_no' => sprintf('DEMO-EMP-%03d', $index + 1)],
                [
                    'user_id' => $teacher->user_id,
                    'teacher_id' => $teacher->id,
                    'department_id' => $teacher->department_id,
                    'job_title' => 'Teacher',
                    'hire_date' => '2024-01-01',
                    'employment_status' => 'active',
                    'base_salary' => (string) (85000 + ($index * 2500)).'.00',
                ]
            );
        }

        if (! LeaveRequest::where('reason', 'Family visit during the autumn mid-term break')->exists()) {
            HrPayrollService::requestLeave([
                'employee_id' => Employee::where('employee_no', 'DEMO-EMP-001')->value('id'),
                'leave_type' => 'annual',
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-22',
                'reason' => 'Family visit during the autumn mid-term break',
            ], $admin->id);
        }

        $run = PayrollRun::whereDate('period_start', '2026-10-01')
            ->whereDate('period_end', '2026-10-31')
            ->first();
        if (! $run) {
            $run = HrPayrollService::generatePayroll('2026-10-01', '2026-10-31', $admin->id);
            $firstEntry = $run->entries()->orderBy('id')->firstOrFail();
            HrPayrollService::updateEntry($firstEntry, '5000.00', '0.00', 'International curriculum coordination allowance');
        }
    }

    private function seedProcurement(User $admin): void
    {
        $supplier = Supplier::updateOrCreate(
            ['name' => 'Lanka Academic Resources (Pvt) Ltd'],
            [
                'contact_name' => 'Kavindu Perera',
                'email' => 'orders@lanka-academic.example',
                'phone' => '+94112674300',
                'address' => 'No. 18, Nawala Road, Colombo 05, Sri Lanka',
                'is_active' => true,
            ]
        );
        if (PurchaseOrder::where('notes', 'Cambridge and IB science laboratory consumables for Term 1')->exists()) {
            return;
        }

        $item = InventoryItem::where('sku', 'DEMO-STAT-001')->firstOrFail();
        $order = ProcurementService::create([
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-01',
            'expected_date' => '2026-10-15',
            'notes' => 'Cambridge and IB science laboratory consumables for Term 1',
            'items' => [[
                'inventory_item_id' => $item->id,
                'quantity_ordered' => 50,
                'unit_cost' => '1.25',
            ]],
        ], $admin->id);
        ProcurementService::submit($order);
        ProcurementService::approve($order, $admin->id);
        ProcurementService::receive($order, [$order->items()->value('id') => 50], $admin->id, 'LAR-DEL-261015', 'Term 1 exercise books and laboratory consumables received');
    }

    private function seedTransport(AcademicYear $year): void
    {
        $vehicleData = [
            ['registration_number' => 'WP ND-4821', 'vehicle_type' => 'School bus', 'capacity' => 40, 'driver_name' => 'Ravi Nair', 'driver_phone' => '+94775123401', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
            ['registration_number' => 'WP NB-3168', 'vehicle_type' => 'Electric school bus', 'capacity' => 40, 'driver_name' => 'Maya Ibrahim', 'driver_phone' => '+94716234502', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
            ['registration_number' => 'WP NC-9074', 'vehicle_type' => 'Minibus', 'capacity' => 18, 'driver_name' => 'Daniel Chen', 'driver_phone' => '+94767345603', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
        ];
        $vehicles = collect($vehicleData)->map(fn (array $data) => TransportVehicle::updateOrCreate(
            ['registration_number' => $data['registration_number']],
            $data
        ));
        $routeData = [
            ['code' => 'COL-NORTH', 'name' => 'Colombo North to IGK Main Campus', 'vehicle_id' => $vehicles[0]->id, 'monthly_fee' => '6500.00', 'is_active' => true],
            ['code' => 'COL-CENTRAL', 'name' => 'Rajagiriya and Colombo Central Shuttle', 'vehicle_id' => $vehicles[1]->id, 'monthly_fee' => '7200.00', 'is_active' => true],
            ['code' => 'COL-SOUTH', 'name' => 'Nugegoda and Colombo South Shuttle', 'vehicle_id' => $vehicles[2]->id, 'monthly_fee' => '5800.00', 'is_active' => true],
        ];
        $routes = collect($routeData)->map(fn (array $data) => TransportRoute::firstOrCreate(
            ['code' => $data['code']],
            $data
        ));
        $stopData = [
            [
                ['name' => 'North Gate Community Centre', 'sequence' => 1, 'address' => '10 North Avenue', 'pickup_time' => '06:50', 'dropoff_time' => '15:40'],
                ['name' => 'Oakwood Library', 'sequence' => 2, 'address' => '24 Oakwood Road', 'pickup_time' => '07:05', 'dropoff_time' => '15:25'],
                ['name' => 'International School Main Gate', 'sequence' => 3, 'address' => 'School campus', 'pickup_time' => '07:35', 'dropoff_time' => '15:00'],
            ],
            [
                ['name' => 'Riverside Sports Centre', 'sequence' => 1, 'address' => '8 River Road', 'pickup_time' => '06:45', 'dropoff_time' => '15:45'],
                ['name' => 'Central Station', 'sequence' => 2, 'address' => '1 Central Square', 'pickup_time' => '07:05', 'dropoff_time' => '15:25'],
                ['name' => 'International School Main Gate', 'sequence' => 3, 'address' => 'School campus', 'pickup_time' => '07:35', 'dropoff_time' => '15:00'],
            ],
            [
                ['name' => 'South Park Entrance', 'sequence' => 1, 'address' => '5 South Park Lane', 'pickup_time' => '06:55', 'dropoff_time' => '15:35'],
                ['name' => 'Garden District Hub', 'sequence' => 2, 'address' => '30 Garden Street', 'pickup_time' => '07:15', 'dropoff_time' => '15:15'],
                ['name' => 'International School Main Gate', 'sequence' => 3, 'address' => 'School campus', 'pickup_time' => '07:40', 'dropoff_time' => '15:00'],
            ],
        ];
        foreach ($routes as $index => $route) {
            foreach ($stopData[$index] as $data) {
                TransportStop::firstOrCreate(
                    ['transport_route_id' => $route->id, 'sequence' => $data['sequence']],
                    $data
                );
            }
        }

        $students = Student::query()
            ->where('academic_year_id', $year->id)
            ->whereHas('user', fn ($query) => $query->where(function ($emails) {
                $emails->where('email', 'like', 'demo.student.%@school.test')
                    ->orWhere('email', 'student@school.test');
            }))
            ->orderBy('id')
            ->get();
        $assignedStudentIds = StudentTransportAssignment::query()
            ->where('academic_year_id', $year->id)
            ->where('status', 'active')
            ->pluck('student_id')
            ->all();

        foreach ($routes as $route) {
            $stop = $route->stops()->orderBy('sequence')->firstOrFail();
            $vehicle = $vehicles->firstWhere('id', $route->vehicle_id);
            $remainingSeats = max(
                0,
                (int) $vehicle->capacity - StudentTransportAssignment::query()
                    ->where('academic_year_id', $year->id)
                    ->where('status', 'active')
                    ->whereHas('route', fn ($query) => $query->where('vehicle_id', $vehicle->id))
                    ->count()
            );
            $group = $students
                ->reject(fn (Student $student) => in_array($student->id, $assignedStudentIds, true))
                ->take($remainingSeats);

            foreach ($group as $student) {
                TransportService::assignStudent(null, [
                    'student_id' => $student->id,
                    'transport_route_id' => $route->id,
                    'transport_stop_id' => $stop->id,
                    'academic_year_id' => $year->id,
                    'start_date' => $year->start_date->toDateString(),
                    'status' => 'active',
                ]);
                $assignedStudentIds[] = $student->id;
            }
        }
    }

    /**
     * Builds stable, synthetic names for demo accounts without changing their credentials.
     */
    private function sampleName(int $number, array $firstNames, array $lastNames): string
    {
        $index = max(0, $number - 1);
        $firstName = $firstNames[$index % count($firstNames)];
        $lastName = $lastNames[intdiv($index, count($firstNames)) % count($lastNames)];

        return $firstName.' '.$lastName;
    }
}
