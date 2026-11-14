<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleInputValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'QA Admin',
            'email' => 'module-input-qa@school.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $admin->syncRoles(['Super Admin', 'admin']);

        return $admin;
    }

    public function test_updated_library_procurement_and_accounting_forms_render(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            'library.dashboard',
            'library.authors.index',
            'library.categories.index',
            'procurement.orders.create',
            'admin.accounts.create',
            'admin.journals.create',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_library_book_fields_are_validated_and_isbn_is_uppercased(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('library.books.store'), [
            'title' => '123 Book',
            'author' => 'Anne 2',
            'category' => '1 Category',
            'isbn' => '123',
            'publication_year' => 1799,
            'copies' => 501,
        ])->assertSessionHasErrors(['title', 'author', 'category', 'isbn', 'publication_year', 'copies']);

        $category = 'Advanced '.str_repeat('A', 90);
        $this->post(route('library.books.store'), [
            'title' => "Book: A Child's Guide & More",
            'author' => "Anne-Marie O'Neil",
            'category' => $category,
            'isbn' => '978-3-16-148410-x',
            'publication_year' => 2026,
            'copies' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('library_books', ['isbn' => '978-3-16-148410-X']);
        $this->assertDatabaseHas('library_categories', ['name' => $category]);
    }

    public function test_procurement_notes_quantity_and_unit_cost_rules_are_enforced(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'QA Supplier', 'is_active' => true]);
        $item = InventoryItem::create([
            'sku' => 'QA-ITEM',
            'name' => 'QA Item',
            'category' => 'Supplies',
            'unit' => 'Each',
            'quantity_on_hand' => 0,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('procurement.orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'notes' => 'Invalid [purchase] = notes',
            'items' => [[
                'inventory_item_id' => $item->id,
                'quantity_ordered' => 0,
                'unit_cost' => '0.00',
            ]],
        ])->assertSessionHasErrors(['notes', 'items.0.quantity_ordered', 'items.0.unit_cost']);
    }

    public function test_account_and_journal_inputs_are_normalized_and_reject_invalid_text(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'code' => 'acc-101',
            'name' => 'Cash & Equivalents',
            'type' => 'asset',
            'description' => 'Cash held by the school',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounts', ['code' => 'ACC-101']);

        $this->post(route('admin.accounts.store'), [
            'code' => 'ACC-102',
            'name' => '2026 Revenue',
            'type' => 'income',
            'description' => 'Invalid = description',
        ])->assertSessionHasErrors(['name', 'description']);

        $accountId = Account::where('code', 'ACC-101')->value('id');
        $this->post(route('admin.journals.store'), [
            'entry_date' => today()->toDateString(),
            'currency' => config('school.default_currency', 'LKR'),
            'description' => '12345',
            'reference' => 'jv-101',
            'lines' => [
                ['account_id' => $accountId, 'debit' => '1.00', 'credit' => '0', 'memo' => '2026'],
                ['account_id' => $accountId, 'debit' => '0', 'credit' => '1.00', 'memo' => 'Cash transfer'],
            ],
        ])->assertSessionHasErrors(['description', 'lines.0.memo']);

        $this->post(route('admin.journals.store'), [
            'entry_date' => today()->toDateString(),
            'currency' => config('school.default_currency', 'LKR'),
            'description' => 'Cash transfer',
            'reference' => 'jv-102',
            'lines' => [
                ['account_id' => $accountId, 'debit' => '1.00', 'credit' => '0', 'memo' => 'Transfer out'],
                ['account_id' => $accountId, 'debit' => '0', 'credit' => '1.00', 'memo' => 'Transfer in'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('journal_entries', ['reference' => 'JV-102']);
    }

    public function test_role_branch_accounting_and_announcement_inputs_are_validated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post(route('admin.roles.store'), [
            'name' => '123, Bad Role',
            'guard_name' => 'web',
        ])->assertSessionHasErrors(['name']);

        $this->post(route('admin.roles.store'), [
            'name' => 'Ops_Lead-1',
            'guard_name' => 'web',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('roles', ['name' => 'Ops_Lead-1']);

        $this->post(route('admin.branches.store'), [
            'name' => '123, Campus',
            'code' => 'north campus',
            'city' => 'New, City',
            'principal_name' => 'Jane 2',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['name', 'code', 'city', 'principal_name', 'email']);

        $this->post(route('admin.branches.store'), [
            'name' => 'North Campus',
            'code' => 'north-01',
            'city' => 'North City',
            'principal_name' => "Jane O'Neil",
            'email' => 'principal@school.test',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('branches', ['code' => 'NORTH-01']);

        $announcement = [
            'body' => 'The school will hold an open day.',
            'target_role' => 'all',
            'published_at' => now()->format('Y-m-d H:i:s'),
        ];
        $this->post(route('admin.announcements.store'), $announcement + [
            'title' => '2026, Invalid title',
        ])->assertSessionHasErrors(['title']);

        $this->post(route('admin.announcements.store'), $announcement + [
            'title' => 'Open Day 2026!',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('announcements', ['title' => 'Open Day 2026!']);

        $income = Account::create([
            'code' => 'QA-INCOME',
            'name' => 'Other Income',
            'type' => 'income',
            'is_active' => true,
        ]);
        $cash = Account::create([
            'code' => '1000',
            'name' => 'Cash in Hand',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $cashflow = [
            'type' => 'income',
            'entry_date' => today()->toDateString(),
            'category_account_id' => $income->id,
            'cash_account_id' => $cash->id,
        ];
        $this->post(route('admin.accounting.transactions.store'), $cashflow + [
            'description' => '2026, Invalid',
            'reference' => 'receipt 1',
            'amount' => '0.00',
        ])->assertSessionHasErrors(['description', 'reference', 'amount']);

        $this->post(route('admin.accounting.transactions.store'), $cashflow + [
            'description' => 'Grant from donors',
            'reference' => 'receipt/100',
            'amount' => '0.01',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('journal_entries', ['reference' => 'RECEIPT/100']);
    }
}
