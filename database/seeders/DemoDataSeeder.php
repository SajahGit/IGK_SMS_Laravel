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
use App\Models\Setting;
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
        if (! Setting::query()->exists() && ! User::query()->exists()) {
            $this->call(SettingSeeder::class);
        }
        if (! GradeScale::query()->where('is_default', true)->exists()) {
            $this->call(GradeScaleSeeder::class);
        }

        $this->call(SuperAdminSeeder::class);
        User::updateOrCreate(
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

        $departments = collect([
            ['code' => 'SCI', 'name' => 'Science'],
            ['code' => 'ART', 'name' => 'Arts'],
            ['code' => 'COM', 'name' => 'Commerce'],
        ])->map(fn (array $data) => Department::firstOrCreate(['code' => $data['code']], ['name' => $data['name']]));

        $programData = [
            ['code' => 'EYEARS', 'name' => 'International Early Years', 'level' => 'early-years', 'department_id' => $departments[1]->id],
            ['code' => 'PRIM', 'name' => 'International Primary', 'level' => 'primary', 'department_id' => $departments[1]->id],
            ['code' => 'LOWSEC', 'name' => 'Cambridge Lower Secondary', 'level' => 'lower-secondary', 'department_id' => $departments[0]->id],
            ['code' => 'IGCSE', 'name' => 'Cambridge International IGCSE', 'level' => 'upper-secondary', 'department_id' => $departments[0]->id],
        ];
        $programs = collect($programData)->map(function (array $data) {
            $program = Program::firstOrCreate(['code' => $data['code']], $data + [
                'duration_terms' => 4,
                'is_active' => true,
            ]);

            return $program;
        });

        $subjectData = [
            ['code' => 'ENP', 'name' => 'Early Literacy and Phonics', 'credit_hours' => 2],
            ['code' => 'NUM', 'name' => 'Early Numeracy', 'credit_hours' => 2],
            ['code' => 'PLS', 'name' => 'Practical Life Skills', 'credit_hours' => 2],
            ['code' => 'SEN', 'name' => 'Sensorial Learning', 'credit_hours' => 2],
            ['code' => 'EXP', 'name' => 'World Discovery', 'credit_hours' => 2],
            ['code' => 'ENG', 'name' => 'English Language', 'credit_hours' => 3],
            ['code' => 'MTH', 'name' => 'Mathematics', 'credit_hours' => 3],
            ['code' => 'BIO', 'name' => 'Biology', 'credit_hours' => 3],
            ['code' => 'CHE', 'name' => 'Chemistry', 'credit_hours' => 3],
            ['code' => 'PHY', 'name' => 'Physics', 'credit_hours' => 3],
            ['code' => 'ECO', 'name' => 'Economics', 'credit_hours' => 3],
            ['code' => 'HIS', 'name' => 'History', 'credit_hours' => 3],
            ['code' => 'GEO', 'name' => 'Geography', 'credit_hours' => 3],
            ['code' => 'ACC', 'name' => 'Accounting', 'credit_hours' => 3],
            ['code' => 'CS', 'name' => 'Computer Science', 'credit_hours' => 3],
            ['code' => 'FRE', 'name' => 'French as a Foreign Language', 'credit_hours' => 3],
            ['code' => 'SPA', 'name' => 'Spanish as a Foreign Language', 'credit_hours' => 3],
            ['code' => 'MAN', 'name' => 'Mandarin Chinese', 'credit_hours' => 3],
            ['code' => 'ARA', 'name' => 'Arabic Language', 'credit_hours' => 3],
            ['code' => 'TAM', 'name' => 'Tamil Language', 'credit_hours' => 3],
            ['code' => 'ARTS', 'name' => 'Art and Design', 'credit_hours' => 2],
            ['code' => 'MUS', 'name' => 'Music', 'credit_hours' => 2],
            ['code' => 'PE', 'name' => 'Physical Education', 'credit_hours' => 1],
            ['code' => 'GPR', 'name' => 'Global Perspectives', 'credit_hours' => 2],
            ['code' => 'BUS', 'name' => 'Business Studies', 'credit_hours' => 3],
            ['code' => 'PSY', 'name' => 'Psychology', 'credit_hours' => 3],
            ['code' => 'ENV', 'name' => 'Environmental Management', 'credit_hours' => 2],
            ['code' => 'LIT', 'name' => 'English Literature', 'credit_hours' => 3],
            ['code' => 'ICT', 'name' => 'Information and Communication Technology', 'credit_hours' => 3],
        ];
        foreach ($subjectData as $data) {
            Subject::firstOrCreate(['code' => $data['code']], $data);
        }
        $subjectOrder = array_flip(array_column($subjectData, 'code'));
        $subjects = Subject::query()
            ->whereIn('code', array_keys($subjectOrder))
            ->get()
            ->sortBy(fn (Subject $subject) => $subjectOrder[$subject->code])
            ->values();

        $classLevels = [
            ['Montessori', 'A', 'EYEARS'],
            ['LKG', 'A', 'EYEARS'],
            ['UKG', 'A', 'EYEARS'],
            ['Grade 1', 'A', 'PRIM'],
            ['Grade 2', 'A', 'PRIM'],
            ['Grade 3', 'A', 'PRIM'],
            ['Grade 4', 'A', 'PRIM'],
            ['Grade 5', 'A', 'PRIM'],
            ['Grade 6', 'A', 'LOWSEC'],
            ['Grade 7', 'A', 'LOWSEC'],
            ['Grade 8', 'A', 'LOWSEC'],
            ['Grade 9', 'A', 'LOWSEC'],
            ['Grade 10', 'A', 'IGCSE'],
            ['Grade 11', 'A', 'IGCSE'],
            ['Grade 12', 'A', 'IGCSE'],
        ];
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H'] as $section) {
            $classLevels[] = ['Grade 10', $section, 'IGCSE'];
        }
        $classes = collect($classLevels)->map(function (array $level) use ($programs, $year) {
            [$name, $section, $programCode] = $level;
            $attributes = [
                'capacity' => 40,
                'program_id' => $programs->firstWhere('code', $programCode)->id,
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
            if (! $class->program_id) {
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

        $this->seedTeachers($departments);
        $teachers = Teacher::query()->orderBy('id')->limit(12)->get();
        $admin = User::where('email', 'admin@school.test')->firstOrFail();
        $this->seedStudents($classes, $programs, $year, $admin);
        $this->seedOfferings($classes, $subjects, $teachers, $term);
        $this->command?->info('Student enrollments ready; generating attendance and HR data...');
        $this->call(LmsDemoSeeder::class);
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
            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $departments[($number - 1) % $departments->count()]->id,
                    'phone' => sprintf('555%07d', $number),
                    'status' => 'active',
                ]
            );
        }

        User::query()->where('email', 'like', 'demo.staff.%@school.test')->get()->each(function (User $user) use ($firstNames, $lastNames): void {
            if (preg_match('/demo\.staff\.(\d+)@school\.test/', $user->email, $matches)) {
                $user->update(['name' => $this->sampleName((int) $matches[1], $firstNames, $lastNames)]);
            }
        });
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  Collection<int, Program>  $programs
     */
    private function seedStudents(Collection $classes, Collection $programs, AcademicYear $year, User $admin): void
    {
        $studentFirstNames = ['Aarav', 'Aisha', 'Amara', 'Anaya', 'Arjun', 'Ava', 'Daniel', 'Ethan', 'Fatima', 'Hana', 'Isabella', 'Kai', 'Leila', 'Liam', 'Maya', 'Mei', 'Noah', 'Olivia', 'Priya', 'Rohan', 'Sofia', 'Zara', 'Zoya', 'Lucas'];
        $guardianFirstNames = ['Adrian', 'Amina', 'Anil', 'Carla', 'David', 'Farah', 'Grace', 'Imran', 'Joseph', 'Meera', 'Ravi', 'Sara', 'Samira', 'Thomas', 'Yasmin', 'Arun', 'Helen', 'Nadia', 'Kiran', 'Rafael', 'Leena', 'Omar', 'Ruth', 'Vikram'];
        $familyNames = ['Abraham', 'Ali', 'Bennett', 'Chen', 'Das', 'Fernandes', 'Garcia', 'Haddad', 'Ibrahim', 'Khan', 'Kim', 'Kumar', 'Lee', 'Martin', 'Mensah', 'Nair', 'Patel', 'Rahman', 'Silva', 'Tan'];
        $students = Student::query()->orderBy('id')->get();
        $classCounts = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $class->students()->count(),
        ]);

        foreach ($students as $student) {
            $class = $classes->firstWhere('id', $student->class_id);
            if ($class && (! $student->program_id || ! $student->academic_year_id)) {
                $student->update([
                    'program_id' => $student->program_id ?: $class->program_id,
                    'academic_year_id' => $student->academic_year_id ?: $class->academic_year_id,
                ]);
            }
        }
        User::query()->where('email', 'like', 'demo.student.%@school.test')->get()->each(function (User $user) use ($studentFirstNames, $familyNames): void {
            if (preg_match('/demo\.student\.(\d+)@school\.test/', $user->email, $matches)) {
                $user->update(['name' => $this->sampleName((int) $matches[1], $studentFirstNames, $familyNames)]);
            }
        });
        User::query()->where('email', 'like', 'demo.guardian.%@school.test')->get()->each(function (User $user) use ($guardianFirstNames, $familyNames): void {
            if (preg_match('/demo\.guardian\.(\d+)@school\.test/', $user->email, $matches)) {
                $user->update(['name' => $this->sampleName((int) $matches[1], $guardianFirstNames, $familyNames)]);
            }
        });
        $guardianLinks = Student::query()
            ->whereNotNull('guardian_id')
            ->get(['id', 'guardian_id'])
            ->map(fn (Student $student) => [
                'guardian_id' => $student->guardian_id,
                'student_id' => $student->id,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();
        foreach (array_chunk($guardianLinks, 500) as $chunk) {
            DB::table('guardian_student')->insertOrIgnore($chunk);
        }

        $next = 1;
        $studentsPerClass = (int) ceil(240 / max(1, $classes->count()));
        while (true) {
            $class = $classes->first(fn (SchoolClass $candidate) => $classCounts[$candidate->id] < $studentsPerClass);
            if (! $class) {
                break;
            }

            do {
                $number = $next++;
                $emails = [
                    sprintf('demo.guardian.%03d@school.test', $number),
                    sprintf('demo.student.%03d@school.test', $number),
                ];
            } while (User::query()->whereIn('email', $emails)->exists());

            $guardianName = $this->sampleName($number, $guardianFirstNames, $familyNames);
            $studentName = $this->sampleName($number, $studentFirstNames, $familyNames);
            $guardianUser = User::create([
                'name' => $guardianName,
                'email' => sprintf('demo.guardian.%03d@school.test', $number),
                'password' => 'password',
                'role' => 'parent',
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $guardian = Guardian::create([
                'user_id' => $guardianUser->id,
                'phone' => sprintf('555%07d', $number),
            ]);

            EnrollmentService::enrollStudent([
                'name' => $studentName,
                'email' => sprintf('demo.student.%03d@school.test', $number),
                'password' => 'password',
                'class_id' => $class->id,
                'program_id' => $class->program_id ?: $programs[($number - 1) % $programs->count()]->id,
                'academic_year_id' => $class->academic_year_id ?: $year->id,
                'primary_guardian_id' => $guardian->id,
                'gender' => $number % 2 === 0 ? 'female' : 'male',
                'dob' => sprintf('%04d-05-%02d', $this->birthYearForClass($class), ($number % 27) + 1),
                'admission_date' => '2026-09-01',
            ]);

            $classCounts->put($class->id, $classCounts[$class->id] + 1);
        }

        if ($admin->exists) {
            $this->command?->info('Seeded '.Student::count().' students.');
        }
    }

    private function birthYearForClass(SchoolClass $class): int
    {
        return match ($class->name) {
            'Montessori' => 2023,
            'LKG' => 2022,
            'UKG' => 2021,
            default => preg_match('/^Grade (\d+)$/', $class->name, $matches) === 1
                ? 2021 - (int) $matches[1]
                : 2010,
        };
    }

    /** @param Collection<int, SchoolClass> $classes
     * @param  EloquentCollection<int, Subject>  $subjects
     * @param  EloquentCollection<int, Teacher>  $teachers
     */
    private function seedOfferings(Collection $classes, EloquentCollection $subjects, EloquentCollection $teachers, Term $term): void
    {
        foreach ($classes as $class) {
            $subjectCodes = match (true) {
                in_array($class->name, ['Montessori', 'LKG', 'UKG'], true) => ['ENP', 'NUM', 'PLS', 'SEN', 'EXP', 'ENG', 'MTH', 'ARTS', 'MUS', 'PE'],
                preg_match('/^Grade ([1-5])$/', $class->name) === 1 => ['ENG', 'MTH', 'BIO', 'HIS', 'GEO', 'ICT', 'ARTS', 'MUS', 'PE', 'GPR'],
                preg_match('/^Grade ([6-9])$/', $class->name) === 1 => ['ENG', 'MTH', 'BIO', 'CHE', 'HIS', 'GEO', 'ICT', 'FRE', 'ARTS', 'PE'],
                default => ['ENG', 'MTH', 'BIO', 'CHE', 'PHY', 'ECO', 'HIS', 'GEO', 'ACC', 'CS'],
            };
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
        $samples = [
            ['Montessori', ['08:00', '08:45']],
            ['Grade 5', ['09:30', '10:15']],
            ['Grade 10', ['11:00', '11:45']],
        ];

        foreach ($samples as [$className, $times]) {
            $class = $classes->firstWhere('name', $className);
            if (! $class) {
                continue;
            }

            $offerings = ClassSubject::query()
                ->where('class_id', $class->id)
                ->where('term_id', $term->id)
                ->orderBy('id')
                ->take(10)
                ->get();

            foreach (range(1, 5) as $day) {
                foreach ($offerings->slice(($day - 1) * 2, 2)->values() as $period => $offering) {
                    $start = $times[$period].':00';
                    $end = date('H:i:s', strtotime($start.' +40 minutes'));
                    TimetableSlot::firstOrCreate(
                        [
                            'class_subject_id' => $offering->id,
                            'day_of_week' => $day,
                            'start_time' => $start,
                            'end_time' => $end,
                        ],
                        ['room' => 'Demo '.$class->name.' '.$class->section]
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

                $rows = Student::where('class_id', $class->id)->get(['id'])->map(fn (Student $student) => [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'marks' => 55 + (($student->id * 17 + $offering->subject_id * 13) % 46),
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
            FeeStructure::firstOrCreate(
                [
                    'name' => 'International School Term Tuition',
                    'program_id' => $program->id,
                    'academic_year_id' => $year->id,
                    'class_id' => null,
                    'term_id' => $term->id,
                ],
                ['amount' => '45000.00', 'is_active' => true]
            );
        }

        foreach ($classes as $class) {
            $feeStructureIds = FeeStructure::query()
                ->whereNull('class_id')
                ->where('program_id', $class->program_id)
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

        $index = 0;
        foreach (DB::table('invoices')->where('term_id', $term->id)->orderBy('id')->get() as $invoice) {
            if ((float) $invoice->balance <= 0 || (float) $invoice->amount_paid > 0) {
                continue;
            }

            if ($index % 10 === 0) {
                PaymentService::recordPayment($invoice->id, $invoice->balance, 'cash', 'DEMO-'.$invoice->id, today()->toDateString(), $admin->id);
            } elseif ($index % 10 === 1) {
                $amount = min(5000, (int) $invoice->balance);
                if ($amount > 0) {
                    PaymentService::recordPayment($invoice->id, (string) $amount, 'cash', 'DEMO-'.$invoice->id, today()->toDateString(), $admin->id);
                }
            }

            $index++;
        }
    }

    private function seedInventory(User $admin): void
    {
        $items = [
            ['sku' => 'DEMO-LAB-001', 'name' => 'Science Lab Kit', 'category' => 'Laboratory', 'unit' => 'kit', 'quantity' => 24, 'reorder_level' => 5],
            ['sku' => 'DEMO-STA-001', 'name' => 'Student Desk', 'category' => 'Furniture', 'unit' => 'piece', 'quantity' => 48, 'reorder_level' => 8],
            ['sku' => 'DEMO-STAT-001', 'name' => 'Exercise Book', 'category' => 'Stationery', 'unit' => 'book', 'quantity' => 180, 'reorder_level' => 50],
            ['sku' => 'DEMO-IT-001', 'name' => 'Keyboard', 'category' => 'IT Equipment', 'unit' => 'piece', 'quantity' => 12, 'reorder_level' => 4],
            ['sku' => 'DEMO-IT-002', 'name' => 'Projector Lamp', 'category' => 'IT Equipment', 'unit' => 'piece', 'quantity' => 2, 'reorder_level' => 3],
        ];

        foreach ($items as $data) {
            $initialQuantity = $data['quantity'];
            unset($data['quantity']);
            $item = InventoryItem::firstOrCreate(
                ['sku' => $data['sku']],
                $data + ['is_active' => true, 'description' => 'Sample inventory item']
            );
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

        if (! LeaveRequest::where('reason', 'Demo leave request')->exists()) {
            HrPayrollService::requestLeave([
                'employee_id' => Employee::where('employee_no', 'DEMO-EMP-001')->value('id'),
                'leave_type' => 'annual',
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-22',
                'reason' => 'Demo leave request',
            ], $admin->id);
        }

        $run = PayrollRun::whereDate('period_start', '2026-10-01')
            ->whereDate('period_end', '2026-10-31')
            ->first();
        if (! $run) {
            $run = HrPayrollService::generatePayroll('2026-10-01', '2026-10-31', $admin->id);
            $firstEntry = $run->entries()->orderBy('id')->firstOrFail();
            HrPayrollService::updateEntry($firstEntry, '5000.00', '0.00', 'Demo allowance');
        }
    }

    private function seedProcurement(User $admin): void
    {
        $supplier = Supplier::firstOrCreate(
            ['name' => 'Demo School Supplies'],
            [
                'contact_name' => 'Demo Supplier',
                'email' => 'demo.supplier@school.test',
                'phone' => '5550000000',
                'address' => 'Demo data only',
                'is_active' => true,
            ]
        );
        if (PurchaseOrder::where('notes', 'Demo procurement sample')->exists()) {
            return;
        }

        $item = InventoryItem::where('sku', 'DEMO-STAT-001')->firstOrFail();
        $order = ProcurementService::create([
            'supplier_id' => $supplier->id,
            'order_date' => '2026-10-01',
            'expected_date' => '2026-10-15',
            'notes' => 'Demo procurement sample',
            'items' => [[
                'inventory_item_id' => $item->id,
                'quantity_ordered' => 50,
                'unit_cost' => '1.25',
            ]],
        ], $admin->id);
        ProcurementService::submit($order);
        ProcurementService::approve($order, $admin->id);
        ProcurementService::receive($order, [$order->items()->value('id') => 50], $admin->id, 'DEMO-DELIVERY', 'Sample received goods');
    }

    private function seedTransport(AcademicYear $year): void
    {
        $vehicleData = [
            ['registration_number' => 'DEMO-BUS-01', 'vehicle_type' => 'School bus', 'capacity' => 40, 'driver_name' => 'Ravi Nair', 'driver_phone' => '+1-555-0201', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
            ['registration_number' => 'DEMO-BUS-02', 'vehicle_type' => 'Electric school bus', 'capacity' => 40, 'driver_name' => 'Maya Ibrahim', 'driver_phone' => '+1-555-0202', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
            ['registration_number' => 'DEMO-VAN-01', 'vehicle_type' => 'Minibus', 'capacity' => 40, 'driver_name' => 'Daniel Chen', 'driver_phone' => '+1-555-0203', 'insurance_expiry' => '2027-08-31', 'inspection_expiry' => '2027-06-30', 'status' => 'active'],
        ];
        $vehicles = collect($vehicleData)->map(fn (array $data) => TransportVehicle::firstOrCreate(
            ['registration_number' => $data['registration_number']],
            $data
        ));
        $routeData = [
            ['code' => 'DEMO-NORTH', 'name' => 'North Residential Loop', 'vehicle_id' => $vehicles[0]->id, 'monthly_fee' => '6500.00', 'is_active' => true],
            ['code' => 'DEMO-RIVERSIDE', 'name' => 'Riverside and Central Route', 'vehicle_id' => $vehicles[1]->id, 'monthly_fee' => '7200.00', 'is_active' => true],
            ['code' => 'DEMO-SOUTH', 'name' => 'South Park and Garden Route', 'vehicle_id' => $vehicles[2]->id, 'monthly_fee' => '5800.00', 'is_active' => true],
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
            ->limit(90)
            ->get();
        foreach ($students->chunk(30)->values() as $index => $group) {
            $route = $routes[$index];
            $stop = $route->stops()->orderBy('sequence')->firstOrFail();
            foreach ($group as $student) {
                if (StudentTransportAssignment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->exists()) {
                    continue;
                }

                TransportService::assignStudent(null, [
                    'student_id' => $student->id,
                    'transport_route_id' => $route->id,
                    'transport_stop_id' => $stop->id,
                    'academic_year_id' => $year->id,
                    'start_date' => $year->start_date->toDateString(),
                    'status' => 'active',
                ]);
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
