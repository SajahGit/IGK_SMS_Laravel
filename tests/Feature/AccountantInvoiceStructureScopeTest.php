<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

class AccountantInvoiceStructureScopeTest extends TestCase
{
    use CreatesFinanceFixtures;
    use RefreshDatabase;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $this->accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'structure-scope-finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $this->accountant->syncRoles(['Accountant']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_term_three_class_structure_is_listed_and_invoiced_with_discount_in_program_currency(): void
    {
        ['year' => $year, 'program' => $program, 'class' => $class, 'term2' => $term2, 'term3' => $term3] = $this->makeContext();
        $student = $this->makeStudentIn($class);
        $termThreeFee = $this->makeFee($class, $program, $year, $term3, 'IGCSE Term 3 Science & Lab Package 2026', '2000.00');
        $this->makeFee($class, $program, $year, $term2, 'Term 2 Package', '900.00');

        $this->actingAs($this->accountant)
            ->get(route('accountant.invoices.create', [
                'class_id' => $class->id,
                'term_id' => $term3->id,
                'currency' => 'GBP',
            ]))
            ->assertOk()
            ->assertSee('IGCSE Term 3 Science & Lab Package 2026')
            ->assertDontSee('Term 2 Package')
            ->assertSee('GBP 2,000.00')
            ->assertSee('GBP 0.00');

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), [
                'class_id' => $class->id,
                'term_id' => $term3->id,
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays(30)->toDateString(),
                'fee_structure_ids' => [$termThreeFee->id],
                'discount' => '250.00',
            ])
            ->assertRedirect();

        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('GBP', $invoice->currency);
        $this->assertSame('2000.00', $invoice->subtotal);
        $this->assertSame('250.00', $invoice->discount);
        $this->assertSame('1750.00', $invoice->total);
    }

    public function test_program_wide_term_fee_is_available_for_classes_in_same_program(): void
    {
        ['year' => $year, 'program' => $program, 'class' => $class, 'term3' => $term3] = $this->makeContext();
        $this->makeFee($class, $program, $year, $term3, 'Original Class Only', '100.00');
        $otherClass = SchoolClass::create([
            'name' => 'Grade 9',
            'section' => 'B',
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);
        $student = $this->makeStudentIn($otherClass);
        $programFee = FeeStructure::create([
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'term_id' => $term3->id,
            'name' => 'IGCSE Program Term 3 Fee',
            'amount' => '1500.00',
            'currency' => 'GBP',
            'is_active' => true,
        ]);
        $yearWideFee = FeeStructure::create([
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'name' => 'IGCSE Annual Program Fee',
            'amount' => '500.00',
            'currency' => 'GBP',
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant)
            ->get(route('accountant.invoices.create', [
                'class_id' => $otherClass->id,
                'term_id' => $term3->id,
                'currency' => 'GBP',
            ]))
            ->assertOk()
            ->assertSee('IGCSE Program Term 3 Fee')
            ->assertSee('IGCSE Annual Program Fee')
            ->assertDontSee('Original Class Only');

        $this->get(route('accountant.invoices.create', [
            'class_id' => $otherClass->id,
            'term_id' => '',
            'currency' => 'GBP',
        ]))
            ->assertOk()
            ->assertSee('IGCSE Annual Program Fee')
            ->assertDontSee('IGCSE Program Term 3 Fee');

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), [
                'class_id' => $otherClass->id,
                'term_id' => $term3->id,
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays(30)->toDateString(),
                'fee_structure_ids' => [$programFee->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'currency' => 'GBP',
            'total' => '1500.00',
        ]);
    }

    public function test_currency_filter_and_store_reject_mixed_or_out_of_scope_fee_structures(): void
    {
        ['year' => $year, 'program' => $program, 'class' => $class, 'term3' => $term3] = $this->makeContext();
        $student = $this->makeStudentIn($class);
        $gbpFee = $this->makeFee($class, $program, $year, $term3, 'GBP Science Package', '1000.00');
        $usdFee = FeeStructure::create([
            'class_id' => $class->id,
            'term_id' => $term3->id,
            'name' => 'USD Science Package',
            'amount' => '1200.00',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        $otherProgram = $this->makeProgram('OTHER-PROGRAM');
        $otherClass = SchoolClass::create([
            'name' => 'Grade 9',
            'section' => 'B',
            'program_id' => $otherProgram->id,
            'academic_year_id' => $year->id,
        ]);
        $outOfScopeFee = FeeStructure::create([
            'class_id' => $otherClass->id,
            'term_id' => $term3->id,
            'name' => 'Other Class Package',
            'amount' => '500.00',
            'currency' => 'GBP',
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant)
            ->get(route('accountant.invoices.create', [
                'class_id' => $class->id,
                'term_id' => $term3->id,
                'currency' => 'GBP',
            ]))
            ->assertOk()
            ->assertSee('GBP Science Package')
            ->assertDontSee('USD Science Package')
            ->assertSee('GBP 0.00');

        $basePayload = [
            'class_id' => $class->id,
            'term_id' => $term3->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
        ];

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), $basePayload + [
                'fee_structure_ids' => [$gbpFee->id, $usdFee->id],
            ])
            ->assertSessionHasErrors([
                'fee_structure_ids' => 'Select fee structures in one currency only.',
            ]);

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), $basePayload + [
                'fee_structure_ids' => [$gbpFee->id, $outOfScopeFee->id],
            ])
            ->assertSessionHasErrors([
                'fee_structure_ids' => 'Every selected fee structure must be active and apply to the target class, program, academic year, and term.',
            ]);

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), $basePayload + [
                'fee_structure_ids' => [$gbpFee->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'currency' => 'GBP',
            'total' => '1000.00',
        ]);
        $this->assertSame(1, Invoice::count());
    }

    private function makeContext(): array
    {
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);
        $program = $this->makeProgram('CA-IGCSE');
        $class = SchoolClass::create([
            'name' => 'Grade 9',
            'section' => 'A',
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);
        $term2 = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'sequence' => 2,
            'start_date' => '2027-01-01',
            'end_date' => '2027-04-30',
            'is_current' => false,
        ]);
        $term3 = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 3',
            'sequence' => 3,
            'start_date' => '2027-05-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);

        return compact('year', 'program', 'class', 'term2', 'term3');
    }

    private function makeProgram(string $code): Program
    {
        $department = Department::create([
            'name' => 'Science '.$code,
            'code' => 'SCI-'.$code,
            'is_active' => true,
        ]);

        return Program::create([
            'department_id' => $department->id,
            'name' => 'Programme '.$code,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    private function makeFee(
        SchoolClass $class,
        Program $program,
        AcademicYear $year,
        Term $term,
        string $name,
        string $amount
    ): FeeStructure {
        return FeeStructure::create([
            'class_id' => $class->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => $name,
            'amount' => $amount,
            'currency' => 'GBP',
            'is_active' => true,
        ]);
    }
}
