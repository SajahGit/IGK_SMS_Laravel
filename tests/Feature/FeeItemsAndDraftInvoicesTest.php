<?php

namespace Tests\Feature;

use App\Events\InvoiceIssued;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP M19 (fee items, mandatory / optional) and M20 (draft -> issue).
 */
class FeeItemsAndDraftInvoicesTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();

        $this->accountant = User::create([
            'name' => 'Finance Officer',
            'email' => 'items-finance@school.test',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $this->accountant->syncRoles(['Accountant']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** A structure with two mandatory items (7500 + 500) and one optional item (1000). */
    private function structureWithItems($class): FeeStructure
    {
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Term 1', 'amount' => '1.00']);
        $fee->items()->create(['name' => 'Tuition', 'amount' => '7500.00', 'is_mandatory' => true]);
        $fee->items()->create(['name' => 'Lab', 'amount' => '500.00', 'is_mandatory' => true]);
        $fee->items()->create(['name' => 'Transport', 'amount' => '1000.00', 'is_mandatory' => false]);
        $fee->syncAmountFromItems();

        return $fee->fresh();
    }

    public function test_structure_amount_is_the_sum_of_its_mandatory_items(): void
    {
        $fee = $this->structureWithItems($this->makeClass());

        $this->assertSame('8000.00', (string) $fee->amount);
    }

    public function test_invoice_gets_one_line_per_mandatory_item_and_skips_optional_ones(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = $this->structureWithItems($class);

        $invoice = InvoiceService::generateForStudent($student, null, '2026-01-10', '2026-02-10', collect([$fee]));

        $this->assertSame('8000.00', (string) $invoice->total);
        $this->assertCount(2, $invoice->items);
        $this->assertNotNull($invoice->items->first()->fee_item_id);
        $this->assertSame('issued', $invoice->status);
    }

    public function test_a_ticked_optional_item_is_billed(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = $this->structureWithItems($class);
        $optionalId = $fee->items()->where('is_mandatory', false)->value('id');

        $invoice = InvoiceService::generateForStudent(
            $student, null, '2026-01-10', '2026-02-10', collect([$fee]), '0.00', [$optionalId]
        );

        $this->assertSame('9000.00', (string) $invoice->total);
        $this->assertCount(3, $invoice->items);
    }

    public function test_a_structure_without_items_still_bills_its_own_amount(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '5000.00']);

        $invoice = InvoiceService::generateForStudent($student, null, '2026-01-10', '2026-02-10', collect([$fee]));

        $this->assertSame('5000.00', (string) $invoice->total);
        $this->assertCount(1, $invoice->items);
        $this->assertNull($invoice->items->first()->fee_item_id);
    }

    public function test_accountant_can_add_and_remove_items_before_any_invoice(): void
    {
        $class = $this->makeClass();
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Term 1', 'amount' => '100.00']);

        $this->actingAs($this->accountant)
            ->post(route('accountant.fee-structures.items.store', $fee), [
                'name' => 'Tuition', 'amount' => '4000.00', 'is_mandatory' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('4000.00', (string) $fee->fresh()->amount);
        $item = $fee->items()->first();
        $this->assertNotNull($item);

        $this->actingAs($this->accountant)
            ->delete(route('accountant.fee-items.destroy', $item))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $fee->items()->count());
    }

    public function test_items_cannot_change_once_an_invoice_exists(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = $this->structureWithItems($class);
        InvoiceService::generateForStudent($student, null, '2026-01-10', '2026-02-10', collect([$fee]));

        $this->actingAs($this->accountant)
            ->post(route('accountant.fee-structures.items.store', $fee), [
                'name' => 'Extra', 'amount' => '10.00', 'is_mandatory' => '1',
            ])
            ->assertSessionHasErrors('fee_structure');

        $this->actingAs($this->accountant)
            ->delete(route('accountant.fee-items.destroy', $fee->items()->first()))
            ->assertSessionHasErrors('fee_structure');

        $this->assertSame(3, $fee->items()->count());
    }

    public function test_draft_invoice_is_not_announced_takes_no_payment_and_is_not_counted(): void
    {
        Event::fake([InvoiceIssued::class]);

        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '5000.00']);

        $invoice = InvoiceService::generateForStudent(
            $student, null, '2026-01-10', '2026-02-10', collect([$fee]), '0.00', [], true
        );

        $this->assertSame('draft', $invoice->status);
        Event::assertNotDispatched(InvoiceIssued::class);
        $this->assertSame(0, Invoice::live()->count());

        try {
            PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, '2026-01-11', $this->accountant->id);
            $this->fail('A draft invoice must not accept a payment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }

    public function test_issuing_a_draft_announces_it_and_opens_it_for_payment(): void
    {
        Event::fake([InvoiceIssued::class]);

        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '5000.00']);
        $invoice = InvoiceService::generateForStudent(
            $student, null, '2026-01-10', '2026-02-10', collect([$fee]), '0.00', [], true
        );

        $issued = InvoiceService::issue($invoice);

        $this->assertSame('issued', $issued->status);
        Event::assertDispatched(InvoiceIssued::class, 1);

        $payment = PaymentService::recordPayment($invoice->id, '1000.00', 'cash', null, '2026-01-11', $this->accountant->id);
        $this->assertSame('partially_paid', $invoice->fresh()->status);
        $this->assertNotNull($payment->receipt_no);

        $this->expectException(ValidationException::class);
        InvoiceService::issue($invoice->fresh());
    }

    public function test_accountant_can_issue_a_draft_over_http_and_bulk_issue_a_class(): void
    {
        $class = $this->makeClass();
        $first = $this->makeStudentIn($class);
        $second = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '5000.00']);

        $one = InvoiceService::generateForStudent($first, null, '2026-01-10', '2026-02-10', collect([$fee]), '0.00', [], true);
        $two = InvoiceService::generateForStudent($second, null, '2026-01-10', '2026-02-10', collect([$fee]), '0.00', [], true);

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.issue', $one))
            ->assertRedirect();
        $this->assertSame('issued', $one->fresh()->status);

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.issue-drafts'), ['class_id' => $class->id])
            ->assertRedirect();
        $this->assertSame('issued', $two->fresh()->status);
    }

    public function test_bulk_generation_form_can_create_drafts_and_verify_integrity_accepts_them(): void
    {
        $class = $this->makeClass();
        $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '5000.00']);

        $this->actingAs($this->accountant)
            ->post(route('accountant.invoices.store'), [
                'class_id' => $class->id,
                'issue_date' => '2026-01-10',
                'due_date' => '2026-02-10',
                'fee_structure_ids' => [$fee->id],
                'as_draft' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Invoice::where('status', 'draft')->count());
        $this->artisan('school:verify-integrity')->assertSuccessful();
    }
}
