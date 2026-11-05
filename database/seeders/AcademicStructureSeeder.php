<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Department::firstOrCreate(['code' => 'SCI'], ['name' => 'Science']);
        \App\Models\Department::firstOrCreate(['code' => 'ART'], ['name' => 'Arts']);
        \App\Models\Department::firstOrCreate(['code' => 'COM'], ['name' => 'Commerce']);

        $deptSci = \App\Models\Department::where('code', 'SCI')->first();
        \App\Models\Program::firstOrCreate(['code' => 'BSC'], ['name' => 'Bachelor of Science', 'department_id' => $deptSci?->id]);
        \App\Models\Program::firstOrCreate(['code' => 'BA'], ['name' => 'Bachelor of Arts']);
        \App\Models\Program::firstOrCreate(['code' => 'BCOM'], ['name' => 'Bachelor of Commerce']);

        $subjects = ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English', 'History', 'Economics', 'Accounting'];
        foreach ($subjects as $s) {
            \App\Models\Subject::firstOrCreate(['code' => strtoupper(substr($s,0,3)).rand(100,999)], ['name' => $s, 'credit_hours' => 3]);
        }
    }
}
