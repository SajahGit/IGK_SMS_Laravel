<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\GradeScale;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GradeScaleSeeder::class);

        $departments = collect([
            ['code' => 'EARLY', 'name' => 'Early Years'],
            ['code' => 'LANG', 'name' => 'Languages'],
            ['code' => 'MATH', 'name' => 'Mathematics'],
            ['code' => 'SCI', 'name' => 'Sciences'],
            ['code' => 'HUM', 'name' => 'Humanities'],
            ['code' => 'ARTS', 'name' => 'Creative and Performing Arts'],
            ['code' => 'TECH', 'name' => 'Computing and Design'],
            ['code' => 'WELL', 'name' => 'Physical Education and Wellbeing'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Department::firstOrCreate(['code' => $data['code']], ['name' => $data['name']]),
        ]);

        $scaleIds = GradeScale::query()->pluck('id', 'name');
        $programData = [
            ['code' => 'IBPYP', 'name' => 'International Baccalaureate Primary Years Programme', 'level' => 'early-years-primary', 'department' => 'EARLY', 'scale' => 'IB 1-7', 'duration_terms' => 18],
            ['code' => 'CAMPRIM', 'name' => 'Cambridge International Primary', 'level' => 'primary', 'department' => 'EARLY', 'scale' => 'Cambridge Achievement', 'duration_terms' => 12],
            ['code' => 'AMER-ELEM', 'name' => 'American Elementary School Programme', 'level' => 'primary', 'department' => 'EARLY', 'scale' => 'American GPA', 'duration_terms' => 10],
            ['code' => 'IBMYP', 'name' => 'International Baccalaureate Middle Years Programme', 'level' => 'lower-upper-secondary', 'department' => 'HUM', 'scale' => 'IB 1-7', 'duration_terms' => 10],
            ['code' => 'CAMLS', 'name' => 'Cambridge International Lower Secondary', 'level' => 'lower-secondary', 'department' => 'HUM', 'scale' => 'Cambridge Achievement', 'duration_terms' => 6],
            ['code' => 'AMER-MS', 'name' => 'American Middle School Programme', 'level' => 'lower-secondary', 'department' => 'HUM', 'scale' => 'American GPA', 'duration_terms' => 6],
            ['code' => 'CA-IGCSE', 'name' => 'Cambridge International IGCSE', 'level' => 'upper-secondary', 'department' => 'SCI', 'scale' => 'IGCSE A*-G', 'duration_terms' => 4],
            ['code' => 'ED-IGCSE', 'name' => 'Pearson Edexcel International GCSE', 'level' => 'upper-secondary', 'department' => 'SCI', 'scale' => 'Edexcel International GCSE 9-1', 'duration_terms' => 4],
            ['code' => 'US-HS', 'name' => 'American High School and Advanced Placement', 'level' => 'upper-secondary', 'department' => 'SCI', 'scale' => 'American GPA', 'duration_terms' => 8],
            ['code' => 'IBDP', 'name' => 'International Baccalaureate Diploma Programme', 'level' => 'sixth-form', 'department' => 'HUM', 'scale' => 'IB 1-7', 'duration_terms' => 4],
            ['code' => 'CAM-AL', 'name' => 'Cambridge International AS and A Level', 'level' => 'sixth-form', 'department' => 'SCI', 'scale' => 'A-Level A*-E', 'duration_terms' => 4],
            ['code' => 'ED-AL', 'name' => 'Pearson Edexcel International A Level', 'level' => 'sixth-form', 'department' => 'SCI', 'scale' => 'A-Level A*-E', 'duration_terms' => 4],
        ];

        $programs = collect($programData)->mapWithKeys(function (array $data) use ($departments, $scaleIds) {
            $program = Program::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'department_id' => $departments[$data['department']]->id,
                    'grade_scale_id' => $scaleIds[$data['scale']] ?? null,
                    'duration_terms' => $data['duration_terms'],
                    'level' => $data['level'],
                    'is_active' => true,
                ]
            );

            return [$data['code'] => $program];
        });

        $subjectData = [
            ['ENP', 'Early Literacy and Phonics', 2],
            ['NUM', 'Early Numeracy', 2],
            ['PLS', 'Practical Life and Wellbeing', 2],
            ['SEN', 'Sensorial Learning', 2],
            ['WLD', 'People, Places and World Discovery', 2],
            ['ENG', 'English Language', 3],
            ['LIT', 'English Literature', 3],
            ['MTH', 'Mathematics', 3],
            ['FMA', 'Further Mathematics', 3],
            ['BIO', 'Biology', 3],
            ['CHE', 'Chemistry', 3],
            ['PHY', 'Physics', 3],
            ['SCI', 'Integrated Science', 3],
            ['HIS', 'History', 3],
            ['GEO', 'Geography', 3],
            ['ECO', 'Economics', 3],
            ['BUS', 'Business Studies', 3],
            ['ACC', 'Accounting', 3],
            ['PSY', 'Psychology', 3],
            ['GPR', 'Global Perspectives', 2],
            ['FRE', 'French', 2],
            ['SPA', 'Spanish', 2],
            ['MAN', 'Mandarin Chinese', 2],
            ['ARA', 'Arabic', 2],
            ['TAM', 'Tamil', 2],
            ['SIN', 'Sinhala', 2],
            ['HIN', 'Hindi', 2],
            ['JPN', 'Japanese', 2],
            ['SWA', 'Kiswahili', 2],
            ['MAL', 'Malay', 2],
            ['ZUL', 'isiZulu', 2],
            ['AFR', 'Afrikaans', 2],
            ['ART', 'Art and Design', 2],
            ['MUS', 'Music', 2],
            ['PE', 'Physical and Health Education', 1],
            ['ICT', 'Information and Communication Technology', 3],
            ['CS', 'Computer Science', 3],
            ['ENV', 'Environmental Management', 2],
            ['TOK', 'Theory of Knowledge', 2],
            ['EE', 'Extended Essay', 2],
            ['CAS', 'Creativity, Activity and Service', 2],
            ['APCALC', 'AP Calculus', 3],
            ['APBIO', 'AP Biology', 3],
            ['APCHEM', 'AP Chemistry', 3],
            ['APPHYS', 'AP Physics', 3],
            ['APCS', 'AP Computer Science', 3],
            ['APUSH', 'AP United States History', 3],
            ['APENG', 'AP English Language and Composition', 3],
            ['APECON', 'AP Economics', 3],
        ];

        $subjects = collect($subjectData)->mapWithKeys(fn (array $data) => [
            $data[0] => Subject::firstOrCreate(
                ['code' => $data[0]],
                ['name' => $data[1], 'credit_hours' => $data[2]]
            ),
        ]);

        $curricula = [
            'IBPYP' => ['ENP', 'NUM', 'PLS', 'SEN', 'WLD', 'ENG', 'MTH', 'SCI', 'GPR', 'ART', 'MUS', 'PE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'FRE', 'SPA'],
            'CAMPRIM' => ['ENG', 'MTH', 'SCI', 'HIS', 'GEO', 'ICT', 'FRE', 'ART', 'MUS', 'PE', 'GPR', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'AMER-ELEM' => ['ENG', 'MTH', 'SCI', 'HIS', 'GEO', 'ICT', 'ART', 'MUS', 'PE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'FRE', 'SPA'],
            'IBMYP' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'FRE', 'GPR', 'ART', 'MUS', 'PE', 'CS', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'CAMLS' => ['ENG', 'MTH', 'SCI', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ICT', 'FRE', 'GPR', 'ART', 'PE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'AMER-MS' => ['ENG', 'MTH', 'SCI', 'HIS', 'GEO', 'ICT', 'ART', 'MUS', 'PE', 'CS', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'FRE', 'SPA'],
            'CA-IGCSE' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'CS', 'FRE', 'ARA', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'ED-IGCSE' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'CS', 'FRE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'US-HS' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'CS', 'APCALC', 'APBIO', 'APCHEM', 'APPHYS', 'APCS', 'APUSH', 'APENG', 'APECON', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'FRE', 'SPA'],
            'IBDP' => ['ENG', 'LIT', 'MTH', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'PSY', 'FRE', 'MAN', 'ART', 'TOK', 'EE', 'CAS', 'SIN', 'HIN', 'TAM', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'CAM-AL' => ['ENG', 'LIT', 'MTH', 'FMA', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'PSY', 'CS', 'FRE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
            'ED-AL' => ['ENG', 'LIT', 'MTH', 'FMA', 'BIO', 'CHE', 'PHY', 'HIS', 'GEO', 'ECO', 'BUS', 'ACC', 'PSY', 'CS', 'FRE', 'SIN', 'HIN', 'TAM', 'MAN', 'JPN', 'ARA', 'SWA', 'MAL', 'ZUL', 'AFR', 'SPA'],
        ];

        foreach ($curricula as $programCode => $subjectCodes) {
            foreach ($subjectCodes as $index => $subjectCode) {
                DB::table('curricula')->insertOrIgnore([
                    'program_id' => $programs[$programCode]->id,
                    'subject_id' => $subjects[$subjectCode]->id,
                    'term_number' => ($index % 2) + 1,
                    'is_elective' => in_array($subjectCode, ['ART', 'MUS', 'PE', 'FRE', 'SPA', 'MAN', 'ARA', 'TAM', 'PSY', 'CS', 'ENV', 'ACC'], true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
