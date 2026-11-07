<?php

namespace Tests\Concerns;

use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

/**
 * Small fixture helpers for finance tests. Tests build their own data (never from seeders).
 */
trait CreatesFinanceFixtures
{
    private int $financeFixtureCounter = 0;

    protected function makeClass(string $name = 'Grade 10', string $section = 'A'): SchoolClass
    {
        return SchoolClass::create(['name' => $name, 'section' => $section, 'capacity' => 40]);
    }

    protected function makeStudentIn(SchoolClass $class): Student
    {
        $this->financeFixtureCounter++;
        $n = $this->financeFixtureCounter;

        $user = User::create([
            'name' => 'Fixture Student '.$n,
            'email' => "fixture-student{$n}@school.test",
            'password' => 'password',
            'role' => 'student',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'admission_no' => sprintf('FX%04d', $n),
        ]);
    }

    protected function makeInvoiceFor(Student $student, string $total = '10000.00'): Invoice
    {
        return Invoice::factory()->create([
            'student_id' => $student->id,
            'total' => $total,
            'balance' => $total,
        ]);
    }
}
