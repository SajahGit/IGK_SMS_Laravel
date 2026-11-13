<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\TeacherSubjectAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TeacherSubjectAssignmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_teachers_receive_matching_departments_and_class_subjects_idempotently(): void
    {
        $maths = Department::create(['code' => 'MATH', 'name' => 'Mathematics']);
        $languages = Department::create(['code' => 'LANG', 'name' => 'Languages']);
        $sciences = Department::create(['code' => 'SCI', 'name' => 'Sciences']);
        $wellbeing = Department::create(['code' => 'WELL', 'name' => 'Physical Education and Wellbeing']);

        $lathif = $this->createTeacher('Mr. Lathif', 'teacher@school.test');
        $nisha = $this->createTeacher('Ms. Nisha', 'teacher2@school.test');
        $mathsTeacher = $this->createTeacher('Elena Abraham', 'maths@school.test', $maths->id);
        $languageTeacher = $this->createTeacher('Kai Chen', 'languages@school.test', $languages->id);
        $scienceTeacher = $this->createTeacher('Fatima Abraham', 'science@school.test', $sciences->id);

        $gradeFive = SchoolClass::create(['name' => 'Grade 5', 'section' => 'A']);
        $gradeEight = SchoolClass::create(['name' => 'Grade 8', 'section' => 'A']);
        $gradeTen = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $gradeEleven = SchoolClass::create(['name' => 'Grade 11', 'section' => 'A']);
        $gradeElevenB = SchoolClass::create(['name' => 'Grade 11', 'section' => 'B']);
        $gradeElevenC = SchoolClass::create(['name' => 'Grade 11', 'section' => 'C']);

        $this->createOffering($gradeFive, 'ENG', $lathif);
        $this->createOffering($gradeEight, 'MTH', $lathif);
        $this->createOffering($gradeEight, 'PE', $lathif);
        $this->createOffering($gradeTen, 'MTH', $mathsTeacher);
        $this->createOffering($gradeTen, 'ENG', $mathsTeacher);
        $this->createOffering($gradeTen, 'BIO', $lathif);
        $this->createOffering($gradeEleven, 'FMA', $nisha);
        $this->createOffering($gradeEleven, 'APENG', $scienceTeacher);
        $this->createOffering($gradeEleven, 'MTH', $scienceTeacher);
        $this->createOffering($gradeElevenB, 'MTH', $scienceTeacher);
        $this->createOffering($gradeElevenC, 'MTH', $scienceTeacher);

        $this->seed(TeacherSubjectAssignmentSeeder::class);
        $this->seed(TeacherSubjectAssignmentSeeder::class);

        $this->assertSame($maths->id, $lathif->fresh()->department_id);
        $this->assertSame($languages->id, $nisha->fresh()->department_id);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeTen->id,
            'subject_id' => Subject::where('code', 'MTH')->value('id'),
            'teacher_id' => $lathif->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeTen->id,
            'subject_id' => Subject::where('code', 'ENG')->value('id'),
            'teacher_id' => $nisha->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeTen->id,
            'subject_id' => Subject::where('code', 'BIO')->value('id'),
            'teacher_id' => $scienceTeacher->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeFive->id,
            'subject_id' => Subject::where('code', 'ENG')->value('id'),
            'teacher_id' => $languageTeacher->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeEight->id,
            'subject_id' => Subject::where('code', 'MTH')->value('id'),
            'teacher_id' => $mathsTeacher->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeEleven->id,
            'subject_id' => Subject::where('code', 'FMA')->value('id'),
            'teacher_id' => $lathif->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeEleven->id,
            'subject_id' => Subject::where('code', 'APENG')->value('id'),
            'teacher_id' => $nisha->id,
        ]);
        $wellbeingTeacher = Teacher::query()->where('department_id', $wellbeing->id)->firstOrFail();
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $gradeEight->id,
            'subject_id' => Subject::where('code', 'PE')->value('id'),
            'teacher_id' => $wellbeingTeacher->id,
        ]);
        $this->assertDatabaseCount('class_subject', 11);

        $teacherForList = Teacher::query()
            ->with(['user', 'department', 'classSubjects.schoolClass', 'classSubjects.subject'])
            ->findOrFail($lathif->id);
        $html = view('components.teacher-table', [
            'teachers' => new LengthAwarePaginator(collect([$teacherForList]), 1, 10, 1),
        ])->render();
        $this->assertStringContainsString('Classes and subjects', $html);
        $this->assertStringContainsString('+2 more', $html);
    }

    private function createTeacher(string $name, string $email, ?int $departmentId = null): Teacher
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'Password123!',
            'role' => 'teacher',
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'status' => 'active',
        ]);
    }

    private function createOffering(SchoolClass $schoolClass, string $code, Teacher $teacher): void
    {
        $subject = Subject::firstOrCreate(['code' => $code], ['name' => $code]);
        ClassSubject::create([
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
    }
}
