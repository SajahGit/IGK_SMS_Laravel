<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42); // same demo data every time

        // Seed roles and permissions (M2 RBAC)
        $this->call(RolePermissionSeeder::class);
        $this->call(SuperAdminSeeder::class);
        $this->call(GradeScaleSeeder::class);

        // ---- Academic Spine ----
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_current' => true,
            ]
        );

        $term = Term::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 1',
            ],
            [
                'sequence' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-04-30',
                'is_current' => true,
            ]
        );

        // ---- Admin ----
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // ---- Classes and subjects ----
        $classA = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A', 'capacity' => 40]);
        $classB = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B', 'capacity' => 40]);

        $maths = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'credit_hours' => 4]);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI', 'credit_hours' => 4]);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG', 'credit_hours' => 3]);
        $ict = Subject::create(['name' => 'ICT', 'code' => 'ICT', 'credit_hours' => 3]);

        // ---- Teachers ----
        $t1 = $this->teacher('Mr. Lathif', 'teacher@school.test', '0771111111');
        $t2 = $this->teacher('Ms. Nisha', 'teacher2@school.test', '0772222222');

        $csA1 = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);
        $csA2 = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $science->id, 'teacher_id' => $t1->id]);
        $csA3 = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $english->id, 'teacher_id' => $t2->id]);
        $csA4 = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $ict->id, 'teacher_id' => $t2->id]);

        $csB1 = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);
        $csB2 = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $science->id, 'teacher_id' => $t1->id]);
        $csB3 = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $english->id, 'teacher_id' => $t2->id]);
        $csB4 = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $ict->id, 'teacher_id' => $t2->id]);

        // ---- Registrar and Accountant (demo accounts) ----
        $registrarUser = User::create([
            'name' => 'Demo Registrar',
            'email' => 'registrar@school.test',
            'password' => 'password',
            'role' => 'registrar',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $accountantUser = User::create([
            'name' => 'Demo Accountant',
            'email' => 'accountant@school.test',
            'password' => 'password',
            'role' => 'accountant',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // ---- Timetable Slots (Monday - Friday) with Zero Clashes ----
        // Class A: Room 101; Class B: Room 102
        // Monday (1)
        TimetableSlot::create(['class_subject_id' => $csA1->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'Room 101']);
        TimetableSlot::create(['class_subject_id' => $csB3->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'Room 102']);

        TimetableSlot::create(['class_subject_id' => $csA3->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'Room 101']);
        TimetableSlot::create(['class_subject_id' => $csB1->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'Room 102']);

        // Tuesday (2)
        TimetableSlot::create(['class_subject_id' => $csA2->id, 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'Science Lab A']);
        TimetableSlot::create(['class_subject_id' => $csB4->id, 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ICT Lab 1']);

        // Wednesday (3)
        TimetableSlot::create(['class_subject_id' => $csA4->id, 'day_of_week' => 3, 'start_time' => '10:30', 'end_time' => '11:30', 'room' => 'ICT Lab 1']);
        TimetableSlot::create(['class_subject_id' => $csB2->id, 'day_of_week' => 3, 'start_time' => '10:30', 'end_time' => '11:30', 'room' => 'Science Lab A']);

        // Thursday (4)
        TimetableSlot::create(['class_subject_id' => $csA1->id, 'day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'Room 101']);
        TimetableSlot::create(['class_subject_id' => $csB3->id, 'day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'Room 102']);

        // Friday (5)
        TimetableSlot::create(['class_subject_id' => $csA2->id, 'day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'Science Lab A']);
        TimetableSlot::create(['class_subject_id' => $csB1->id, 'day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'Room 102']);

        // ---- Guardians ----
        $guardianUser = User::create(['name' => 'Demo Guardian', 'email' => 'guardian@school.test', 'password' => 'password', 'role' => 'parent']);
        $demoGuardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '0773333333']);

        // ---- Students ----
        $students = [];
        $students[] = $this->student('Demo Student', 'student@school.test', $classA, 'ADM001', $demoGuardian);

        $names = ['Aysha Fathima', 'Mohamed Riyas', 'Nusrath Banu', 'Ahamed Safwan', 'Sumaiya Naseer',
            'Rajeeya Begum', 'Hasfa Rahman', 'Imran Khan', 'Zainab Farook', 'Arshad Ali'];

        foreach ($names as $i => $name) {
            $number = $i + 2;
            $guardianUser = User::create([
                'name' => 'Guardian of '.$name,
                'email' => 'guardian'.$number.'@school.test',
                'password' => 'password',
                'role' => 'parent',
            ]);
            $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '077'.str_pad((string) (4000000 + $number), 7, '0', STR_PAD_LEFT)]);

            $students[] = $this->student(
                $name,
                'student'.$number.'@school.test',
                $number % 2 === 0 ? $classA : $classB,
                'ADM'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                $guardian
            );
        }

        // ---- Attendance for the last 14 weekdays ----
        $days = [];
        $cursor = today();
        while (count($days) < 14) {
            if ($cursor->isWeekday() && $cursor->lte(today())) {
                $days[] = $cursor->toDateString();
            }
            $cursor = $cursor->copy()->subDay();
        }

        foreach ($students as $student) {
            foreach ($days as $day) {
                $roll = mt_rand(1, 100);
                $status = $roll <= 82 ? 'present' : ($roll <= 92 ? 'late' : 'absent');
                Attendance::create([
                    'student_id' => $student->id,
                    'class_id' => $student->class_id,
                    'date' => $day,
                    'status' => $status,
                    'marked_by' => $t1->user_id,
                ]);
            }
        }

        // ---- Exams and results ----
        $examDefs = [
            ['Mid Term - Mathematics', $maths, $t1, 30, true],
            ['Mid Term - Science', $science, $t1, 28, true],
            // Left unpublished so teachers can try mark entry in the demo.
            ['Mid Term - English', $english, $t2, 26, false],
        ];

        foreach ([$classA, $classB] as $class) {
            foreach ($examDefs as [$name, $subject, $teacher, $daysAgo, $published]) {
                $exam = Exam::create([
                    'name' => $name,
                    'type' => 'midterm',
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'term_id' => $term->id,
                    'exam_date' => today()->subDays($daysAgo)->toDateString(),
                    'max_marks' => 100,
                    'weight' => 100,
                    'is_published' => $published,
                    'published_at' => $published ? now()->subDays($daysAgo - 2) : null,
                    'created_by' => $teacher->user_id,
                ]);

                foreach ($students as $student) {
                    if ($student->class_id !== $class->id) {
                        continue;
                    }
                    Result::create([
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'marks' => mt_rand(45, 98),
                        'is_absent' => false,
                    ]);
                }
            }
        }

        // ---- Finance: Fee Structures ----
        $f1 = FeeStructure::create([
            'class_id' => $classA->id,
            'term_id' => $term->id,
            'name' => 'Term 1 Tuition Fee',
            'amount' => '15000.00',
            'is_active' => true,
        ]);
        $f2 = FeeStructure::create([
            'class_id' => $classA->id,
            'term_id' => $term->id,
            'name' => 'Science & Computer Lab Fee',
            'amount' => '3500.00',
            'is_active' => true,
        ]);

        FeeStructure::create([
            'class_id' => $classB->id,
            'term_id' => $term->id,
            'name' => 'Term 1 Tuition Fee',
            'amount' => '15000.00',
            'is_active' => true,
        ]);

        // ---- Finance: Invoices & Payments ----
        $yearStr = date('Y');
        foreach ($students as $index => $student) {
            $invNo = 'INV-'.$yearStr.'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $total = '18500.00';

            $invoice = Invoice::create([
                'student_id' => $student->id,
                'term_id' => $term->id,
                'invoice_no' => $invNo,
                'issue_date' => today()->subDays(20)->toDateString(),
                'due_date' => today()->addDays(10)->toDateString(),
                'total' => $total,
                'amount_paid' => '0.00',
                'balance' => $total,
                'status' => 'issued',
            ]);

            // Demo student pays in full, second student pays partial, others pending
            if ($index === 0) {
                PaymentService::recordPayment(
                    $invoice->id,
                    '18500.00',
                    'bank_transfer',
                    'SLIP-882910',
                    today()->subDays(5)->toDateString(),
                    $admin->id
                );
            } elseif ($index === 1) {
                PaymentService::recordPayment(
                    $invoice->id,
                    '10000.00',
                    'cash',
                    'CASH-REC',
                    today()->subDays(2)->toDateString(),
                    $admin->id
                );
            }
        }

        // ---- Announcements ----
        Announcement::create([
            'title' => 'Welcome to the 2026 Academic Year',
            'body' => 'Classes have resumed for all grades. Please ensure all student emergency contact details and health forms are updated.',
            'target_role' => 'all',
            'published_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title' => 'Term 1 Mid-Term Examination Schedule',
            'body' => 'Teacher mark entries for Term 1 midterm exams must be completed by Friday. Marks will be locked once published.',
            'target_role' => 'teacher',
            'published_at' => now()->subDays(3),
            'expires_at' => now()->addDays(15),
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title' => 'Term 1 Fee Invoices Issued',
            'body' => 'Term 1 fee statements have been issued. Please review invoices in the guardian/student portal and complete payment before the due date.',
            'target_role' => 'parent',
            'published_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
            'created_by' => $admin->id,
        ]);

        // Sync spatie roles from role column for all users
        User::all()->each(function ($user) {
            $roleMap = [
                'super-admin' => 'Super Admin',
                'admin' => 'Institution Admin',
                'teacher' => 'Teacher',
                'student' => 'Student',
                'registrar' => 'Registrar',
                'accountant' => 'Accountant',
            ];
            $spatieRole = $roleMap[$user->role] ?? null;
            if ($spatieRole) {
                $user->syncRoles([$spatieRole]);
            }
        });
    }

    private function teacher(string $name, string $email, string $phone): Teacher
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'password', 'role' => 'teacher']);

        return Teacher::create(['user_id' => $user->id, 'phone' => $phone]);
    }

    private function student(string $name, string $email, SchoolClass $class, string $admissionNo, Guardian $guardian): Student
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'password', 'role' => 'student']);

        return Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'guardian_id' => $guardian->id,
            'admission_no' => $admissionNo,
            'dob' => '2010-05-15',
            'phone' => null,
        ]);
    }
}
