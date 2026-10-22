<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42); // same demo data every time

        // ---- Admin ----
        User::create(['name' => 'System Admin', 'email' => 'admin@school.test', 'password' => 'password', 'role' => 'admin']);

        // ---- Classes and subjects ----
        $classA = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $classB = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);

        $maths = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $ict = Subject::create(['name' => 'ICT', 'code' => 'ICT']);

        // ---- Teachers ----
        $t1 = $this->teacher('Mr. Lathif', 'teacher@school.test', '0771111111');
        $t2 = $this->teacher('Ms. Nisha', 'teacher2@school.test', '0772222222');

        foreach ([$classA, $classB] as $class) {
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $science->id, 'teacher_id' => $t1->id]);
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $english->id, 'teacher_id' => $t2->id]);
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $ict->id, 'teacher_id' => $t2->id]);
        }

        // ---- Parents ----
        $parentUser = User::create(['name' => 'Demo Parent', 'email' => 'parent@school.test', 'password' => 'password', 'role' => 'parent']);
        $demoParent = Guardian::create(['user_id' => $parentUser->id, 'phone' => '0773333333']);

        // ---- Students ----
        $students = [];

        // The demo student, linked to the demo parent
        $students[] = $this->student('Demo Student', 'student@school.test', $classA, 'ADM001', $demoParent);

        $names = ['Aysha Fathima', 'Mohamed Riyas', 'Nusrath Banu', 'Ahamed Safwan', 'Sumaiya Naseer',
                  'Rajeeya Begum', 'Hasfa Rahman', 'Imran Khan', 'Zainab Farook', 'Arshad Ali'];

        foreach ($names as $i => $name) {
            $number = $i + 2;
            $parentUser = User::create([
                'name' => 'Parent of '.$name,
                'email' => 'parent'.$number.'@school.test',
                'password' => 'password',
                'role' => 'parent',
            ]);
            $guardian = Guardian::create(['user_id' => $parentUser->id, 'phone' => '077'.str_pad((string) (4000000 + $number), 7, '0', STR_PAD_LEFT)]);

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
            ['Mid Term - Mathematics', $maths, $t1, 30],
            ['Mid Term - Science', $science, $t1, 28],
            ['Mid Term - English', $english, $t2, 26],
        ];

        foreach ([$classA, $classB] as $class) {
            foreach ($examDefs as [$name, $subject, $teacher, $daysAgo]) {
                $exam = Exam::create([
                    'name' => $name,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'exam_date' => today()->subDays($daysAgo)->toDateString(),
                    'max_marks' => 100,
                    'created_by' => $teacher->user_id,
                ]);

                foreach ($students as $student) {
                    if ($student->class_id !== $class->id) {
                        continue;
                    }
                    Result::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'marks' => mt_rand(28, 98)]);
                }
            }
        }
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
