<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\AdmissionNumberGenerator;
use Illuminate\Database\Seeder;

/**
 * Demo data: 1 year, 1 term, 1 class, 2 sections, 6 subjects, 4 teachers,
 * 30 students, 1 parent.   Logins (password = "password"):
 *   teacher@school.test / student@school.test / parent@school.test
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::updateOrCreate(
            ['name' => '2026'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true]
        );

        $term = Term::updateOrCreate(
            ['academic_year_id' => $year->id, 'sequence' => 3],
            ['name' => 'Term 3', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'is_current' => true]
        );

        $class = ClassGroup::updateOrCreate(['name' => 'Grade 10'], ['level' => 10]);
        $sections = [
            Section::updateOrCreate(['class_group_id' => $class->id, 'name' => 'A'], ['capacity' => 40, 'room' => 'Room 101']),
            Section::updateOrCreate(['class_group_id' => $class->id, 'name' => 'B'], ['capacity' => 40, 'room' => 'Room 102']),
        ];

        $subjects = [];
        foreach ([
            'MATH' => 'Mathematics', 'SCI' => 'Science', 'ENG' => 'English',
            'TAM' => 'Tamil', 'ICT' => 'ICT', 'HIS' => 'History',
        ] as $code => $name) {
            $subjects[] = Subject::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        // 4 teachers
        $teachers = [];
        foreach ([
            ['teacher@school.test', 'Sumaiya Teacher'],
            ['teacher2@school.test', 'Farook Master'],
            ['teacher3@school.test', 'Nazeer Sir'],
            ['teacher4@school.test', 'Rizna Miss'],
        ] as $i => [$email, $name]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => config('school.default_password'), 'role' => 'teacher', 'is_active' => true]
            );
            $teachers[] = Staff::updateOrCreate(
                ['user_id' => $user->id],
                ['employee_no' => 'EMP-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'designation' => 'Teacher', 'join_date' => '2020-01-01']
            );
        }

        // class-subject + teacher assignment (round robin)
        $n = 0;
        foreach ($sections as $section) {
            foreach ($subjects as $subject) {
                ClassSubject::updateOrCreate(
                    ['section_id' => $section->id, 'subject_id' => $subject->id, 'term_id' => $term->id],
                    ['staff_id' => $teachers[$n % count($teachers)]->id]
                );
                $n++;
            }
        }

        // 30 students
        $firsts = ['Ahamed', 'Fathima', 'Mohamed', 'Aisha', 'Ibrahim', 'Zainab', 'Hassan', 'Nusrath', 'Rizwan', 'Safa', 'Imran', 'Hana', 'Yusuf', 'Mariam', 'Anas'];
        $lasts = ['Rafeek', 'Nazar', 'Cassim', 'Hameed', 'Lebbe', 'Sulaiman', 'Farook', 'Jabir'];

        for ($i = 1; $i <= 30; $i++) {
            $email = $i === 1 ? 'student@school.test' : "student{$i}@school.test";
            $first = $firsts[($i - 1) % count($firsts)];
            $last = $lasts[($i * 3) % count($lasts)];

            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => "$first $last", 'password' => config('school.default_password'), 'role' => 'student', 'is_active' => true]
            );

            if (! Student::where('user_id', $user->id)->exists()) {
                Student::create([
                    'user_id' => $user->id,
                    'section_id' => $sections[$i % 2]->id,
                    'admission_no' => AdmissionNumberGenerator::next(),
                    'first_name' => $first,
                    'last_name' => $last,
                    'gender' => $i % 2 ? 'Male' : 'Female',
                    'dob' => '2010-0'.(($i % 9) + 1).'-1'.($i % 9),
                    'admission_date' => '2021-01-10',
                    'status' => 'active',
                ]);
            }
        }

        // 1 parent linked to student 1 and 2
        $parentUser = User::updateOrCreate(
            ['email' => 'parent@school.test'],
            ['name' => 'Parent Demo', 'password' => config('school.default_password'), 'role' => 'parent', 'is_active' => true]
        );
        $guardian = Guardian::updateOrCreate(
            ['user_id' => $parentUser->id],
            ['name' => 'Parent Demo', 'relationship' => 'Father', 'phone' => '0771234567', 'email' => 'parent@school.test']
        );
        $children = Student::orderBy('id')->take(2)->pluck('id');
        $guardian->students()->syncWithoutDetaching($children->mapWithKeys(fn ($id) => [$id => ['is_primary' => true]])->all());
    }
}
