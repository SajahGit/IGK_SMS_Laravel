<?php

namespace Tests\Feature;

use Database\Seeders\FullSampleDataSeeder;
use Database\Seeders\PerfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_dataset_uses_international_grade_tracks_and_realistic_family_records(): void
    {
        DB::table('classes')->insert([
            ['name' => 'Grade 10', 'section' => 'A', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Grade 10', 'section' => 'B', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('db:seed', ['--class' => PerfSeeder::class])->assertExitCode(0);

        $this->assertSame(5000, DB::table('students')->count());
        $this->assertSame(0, DB::table('students')->whereNull('document_number')->count());
        $this->assertSame(4760, DB::table('students')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('users.email', 'like', 'perf.student.%@school.test')
            ->where('students.document_type', 'passport')
            ->count());
        $this->assertSame(0, DB::table('classes')->where('name', 'Performance Cohort')->count());
        $this->assertSame(0, DB::table('users')->where('name', 'like', 'Performance %')->count());
        $this->assertSame(0, DB::table('guardians')->where('phone', 'like', '555%')->count());
        $this->assertSame(0, DB::table('students')
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->whereNull('classes.program_id')
            ->count());
        $this->assertSame(0, DB::table('students')
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->whereColumn('students.program_id', '!=', 'classes.program_id')
            ->count());
        $this->assertSame(0, DB::table('students')
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('classes.name', 'Grade 10')
            ->where('classes.section', 'A')
            ->where('users.email', 'like', 'perf.student.%@school.test')
            ->count());
        $performanceNames = DB::table('users')
            ->where('email', 'like', 'perf.student.%@school.test')
            ->pluck('name');
        $this->assertFalse($performanceNames->contains(fn (string $name) => preg_match('/[^a-z ]/i', $name) === 1));
        foreach ([
            'Sri Lankan' => '+94',
            'Indian' => '+91',
            'British' => '+44',
            'American' => '+1',
            'Emirati' => '+971',
            'French' => '+33',
            'Kenyan' => '+254',
            'Chinese' => '+86',
            'Japanese' => '+81',
            'Singaporean' => '+65',
            'South African' => '+27',
            'Australian' => '+61',
        ] as $nationality => $callingCode) {
            $guardianPhones = DB::table('guardians')
                ->join('users', 'users.id', '=', 'guardians.user_id')
                ->where('users.email', 'like', 'perf.guardian.%@school.test')
                ->where('guardians.nationality', $nationality);
            $this->assertGreaterThan(0, (clone $guardianPhones)->count());
            $this->assertSame(0, (clone $guardianPhones)->where('guardians.phone', 'not like', $callingCode.'%')->count());
        }
        $this->assertDatabaseHas('classes', ['name' => 'Grade 6', 'program_id' => DB::table('programs')->where('code', 'IBMYP')->value('id')]);
        $this->assertDatabaseHas('classes', ['name' => 'Grade 10', 'program_id' => DB::table('programs')->where('code', 'CA-IGCSE')->value('id')]);
        $this->assertDatabaseHas('classes', ['name' => 'Grade 12', 'program_id' => DB::table('programs')->where('code', 'IBDP')->value('id')]);

        $counts = [
            'students' => DB::table('students')->count(),
            'users' => DB::table('users')->count(),
            'guardians' => DB::table('guardians')->count(),
            'classes' => DB::table('classes')->count(),
        ];
        $this->artisan('db:seed', ['--class' => PerfSeeder::class])->assertExitCode(0);
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "Re-seeding duplicated {$table}.");
        }
    }

    public function test_full_module_seeder_runs_after_the_five_thousand_student_dataset(): void
    {
        DB::table('classes')->insert([
            ['name' => 'Grade 10', 'section' => 'A', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Grade 10', 'section' => 'B', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('db:seed', ['--class' => PerfSeeder::class])->assertExitCode(0);
        $this->artisan('db:seed', ['--class' => FullSampleDataSeeder::class])->assertExitCode(0);

        $this->assertSame(5000, DB::table('students')->count());
        $this->assertSame(5000, DB::table('students')->whereNotNull('document_number')->count());
        $this->assertGreaterThan(0, DB::table('biometric_events')->count());
    }
}
