<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        Department::firstOrCreate(['code' => 'SCI'], ['name' => 'Science']);
        Department::firstOrCreate(['code' => 'ART'], ['name' => 'Arts']);
        Department::firstOrCreate(['code' => 'COM'], ['name' => 'Commerce']);

        $departments = Department::whereIn('code', ['SCI', 'ART', 'COM'])->get()->keyBy('code');
        Program::firstOrCreate(['code' => 'BSC'], ['name' => 'Bachelor of Science', 'department_id' => $departments['SCI']->id]);
        Program::firstOrCreate(['code' => 'BA'], ['name' => 'Bachelor of Arts', 'department_id' => $departments['ART']->id]);
        Program::firstOrCreate(['code' => 'BCOM'], ['name' => 'Bachelor of Commerce', 'department_id' => $departments['COM']->id]);

        $subjects = [
            'MTH' => 'Mathematics',
            'PHY' => 'Physics',
            'CHE' => 'Chemistry',
            'BIO' => 'Biology',
            'ENG' => 'English',
            'HIS' => 'History',
            'ECO' => 'Economics',
            'ACC' => 'Accounting',
        ];
        foreach ($subjects as $code => $name) {
            Subject::firstOrCreate(['code' => $code], ['name' => $name, 'credit_hours' => 3]);
        }
    }
}
