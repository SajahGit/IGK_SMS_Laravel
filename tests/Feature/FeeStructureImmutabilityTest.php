<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * SOP §11 DB design: "fee_structures ... Cannot edit once invoices
 * reference it — version by creating a new structure." TC-FIN style
 * coverage for the immutability-once-invoiced guard.
 */
class FeeStructureImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        $this->accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $this->accountant->syncRoles(['Accountant']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_fee_structure_can_be_edited_and_deleted_before_any_invoice_exists(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $fee = FeeStructure::create([
            'class_id' => $class->id,
            'name' => 'Term 1 Tuition',
            'amount' => '5000.00',
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant)
            ->put(route('accountant.fee-structures.update', $fee), [
                'class_id' => $class->id,
                'name' => 'Term 1 Tuition (Revised)',
                'amount' => '5500.00',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertEquals('5500.00', $fee->fresh()->amount);

        $this->actingAs($this->accountant)
            ->delete(route('accountant.fee-structures.destroy', $fee))
            ->assertRedirect();

        $this->assertNull(FeeStructure::find($fee->id));
    }

    public function test_fee_structure_cannot_be_edited_once_an_invoice_references_it(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);
        $fee = FeeStructure::create([
            'class_id' => $class->id,
            'name' => 'Term 1 Tuition',
            'amount' => '5000.00',
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $user->id, 'class_id' => $class->id, 'admission_no' => 'ADM900']);

        Invoice::create([
            'student_id' => $student->id,
            'term_id' => $fee->term_id,
            'invoice_no' => 'INV-2026-9001',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'total' => '5000.00',
            'amount_paid' => '0.00',
            'balance' => '5000.00',
            'status' => 'issued',
        ]);

        $this->actingAs($this->accountant)
            ->put(route('accountant.fee-structures.update', $fee), [
                'class_id' => $class->id,
                'name' => 'Term 1 Tuition (Revised)',
                'amount' => '5500.00',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('fee_structure');

        $this->assertEquals('5000.00', $fee->fresh()->amount);

        $this->actingAs($this->accountant)
            ->delete(route('accountant.fee-structures.destroy', $fee))
            ->assertSessionHasErrors('fee_structure');

        $this->assertNotNull(FeeStructure::find($fee->id));
    }
}
