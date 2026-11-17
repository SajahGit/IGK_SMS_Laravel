<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleNotificationBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cafeteria_item_creation_shows_success_notification_exactly_once(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $inventoryItem = InventoryItem::create([
            'name' => 'Flour Bag',
            'sku' => 'SKU-FLOUR-01',
            'category' => 'food',
            'unit' => 'kg',
            'quantity_on_hand' => 50,
            'reorder_level' => 10,
            'unit_cost' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.cafeteria.items.store'), [
                'inventory_item_id' => $inventoryItem->id,
                'name' => 'Chicken Bun',
                'price' => 120.00,
                'description' => 'Freshly baked',
            ]);

        $response->assertOk();
        $content = $response->getContent();

        $count = substr_count($content, 'Cafeteria menu item created.');
        $this->assertSame(1, $count, "The notification message must appear exactly once in the page, but appeared {$count} times.");
    }

    public function test_hostel_creation_shows_success_notification_exactly_once(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.hostels.store'), [
                'name' => 'Nelson Mandela Hall',
                'location' => 'North Campus Block A',
            ]);

        $response->assertOk();
        $content = $response->getContent();

        $count = substr_count($content, 'Hostel created.');
        $this->assertSame(1, $count, "The notification message must appear exactly once in the page, but appeared {$count} times.");
    }

    public function test_newly_added_cafeteria_item_is_visible_and_orderable_by_students(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $stock = InventoryItem::create([
            'name' => 'Fruit Juice Box',
            'sku' => 'SKU-JUICE-01',
            'category' => 'Beverage',
            'unit' => 'box',
            'quantity_on_hand' => 100,
            'reorder_level' => 10,
            'unit_cost' => 50,
            'is_active' => true,
        ]);

        // Admin adds menu item
        $createResponse = $this->actingAs($admin)
            ->post(route('admin.cafeteria.items.store'), [
                'inventory_item_id' => $stock->id,
                'name' => 'Mango Smoothie',
                'price' => 150.00,
                'description' => 'Chilled natural mango',
            ]);
        $createResponse->assertRedirect(route('admin.cafeteria.index'));

        // Create student
        $studentUser = User::factory()->create([
            'role' => 'student',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $studentUser->assignRole('Student');

        $class = \App\Models\SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'capacity' => 30,
        ]);

        $student = \App\Models\Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'admission_no' => 'ADM-TEST-CAF-01',
            'status' => 'active',
        ]);

        // Student views cafeteria
        $studentView = $this->actingAs($studentUser)->get(route('student.cafeteria'));
        $studentView->assertOk();
        $studentView->assertSee('Mango Smoothie');

        // Student places order for new item
        $menuItem = \App\Models\CafeteriaItem::where('name', 'Mango Smoothie')->firstOrFail();
        $orderResponse = $this->actingAs($studentUser)->post(route('student.cafeteria.orders.store'), [
            'order_date' => now()->addDay()->toDateString(),
            'items' => [
                ['cafeteria_item_id' => $menuItem->id, 'quantity' => 2],
            ],
        ]);
        $orderResponse->assertRedirect(route('student.cafeteria'));
        $orderResponse->assertSessionHas('success');

        // Verify stock decremented
        $this->assertSame(98, $stock->fresh()->quantity_on_hand);
    }

    public function test_newly_added_items_appear_at_the_top_of_lists(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $stock = InventoryItem::create([
            'name' => 'Grain Stock',
            'sku' => 'SKU-GRAIN-01',
            'category' => 'Food',
            'unit' => 'kg',
            'quantity_on_hand' => 100,
            'reorder_level' => 10,
            'unit_cost' => 50,
            'is_active' => true,
        ]);

        // Add older item "Alpha Sandwich"
        $this->actingAs($admin)->post(route('admin.cafeteria.items.store'), [
            'inventory_item_id' => $stock->id,
            'name' => 'Alpha Sandwich',
            'price' => 100.00,
        ])->assertRedirect(route('admin.cafeteria.index'));

        $stock2 = InventoryItem::create([
            'name' => 'Juice Stock',
            'sku' => 'SKU-JUICE-99',
            'category' => 'Beverage',
            'unit' => 'bottle',
            'quantity_on_hand' => 50,
            'reorder_level' => 5,
            'unit_cost' => 40,
            'is_active' => true,
        ]);

        // Add newer item "Zeta Drink"
        $this->actingAs($admin)->post(route('admin.cafeteria.items.store'), [
            'inventory_item_id' => $stock2->id,
            'name' => 'Zeta Drink',
            'price' => 80.00,
        ])->assertRedirect(route('admin.cafeteria.index'));

        $cafeteriaResponse = $this->actingAs($admin)->get(route('admin.cafeteria.index'));
        $cafeteriaResponse->assertOk();
        $items = $cafeteriaResponse->viewData('items');
        $this->assertSame('Zeta Drink', $items->first()->name, 'Newly added cafeteria item must appear at the top of the list.');

        // Hostels check: add Alpha Hall then Zeta Hall
        $this->actingAs($admin)->post(route('admin.hostels.store'), ['name' => 'Alpha Hall', 'location' => 'East']);
        $this->actingAs($admin)->post(route('admin.hostels.store'), ['name' => 'Zeta Hall', 'location' => 'West']);

        $hostelResponse = $this->actingAs($admin)->get(route('admin.hostels.index'));
        $hostelResponse->assertOk();
        $hostels = $hostelResponse->viewData('hostels');
        $this->assertSame('Zeta Hall', $hostels->first()->name, 'Newly added hostel must appear at the top of the list.');
    }

    public function test_cafeteria_can_select_stocks_from_any_branch_and_assign_to_any_branch(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $branch1 = \App\Models\Branch::create(['name' => 'Colombo Campus', 'code' => 'CMB', 'is_active' => true]);
        $branch2 = \App\Models\Branch::create(['name' => 'Kandy Campus', 'code' => 'KDY', 'is_active' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        // Stock in Branch 1
        $stockBranch1 = InventoryItem::create([
            'branch_id' => $branch1->id,
            'name' => 'Branch 1 Flour',
            'sku' => 'SKU-FLOUR-B1',
            'category' => 'Food',
            'unit' => 'kg',
            'quantity_on_hand' => 100,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        // Stock in Branch 2
        $stockBranch2 = InventoryItem::create([
            'branch_id' => $branch2->id,
            'name' => 'Branch 2 Tea',
            'sku' => 'SKU-TEA-B2',
            'category' => 'Beverage',
            'unit' => 'pack',
            'quantity_on_hand' => 50,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        // Stock Campus-Wide
        $stockGlobal = InventoryItem::create([
            'branch_id' => null,
            'name' => 'Global Water',
            'sku' => 'SKU-WATER-GL',
            'category' => 'Beverage',
            'unit' => 'bottle',
            'quantity_on_hand' => 200,
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        // Admin (even when in branch 1 context) can select Branch 2 stock and assign to Campus-Wide
        session(['active_branch_id' => $branch1->id]);

        $response1 = $this->actingAs($admin)->post(route('admin.cafeteria.items.store'), [
            'inventory_item_id' => $stockBranch2->id,
            'name' => 'Kandy Tea for All',
            'price' => 75.00,
            'branch_id' => '', // Campus-Wide
        ]);
        $response1->assertRedirect(route('admin.cafeteria.index'));

        // Admin can select Branch 1 stock and assign to Branch 2
        $response2 = $this->actingAs($admin)->post(route('admin.cafeteria.items.store'), [
            'inventory_item_id' => $stockBranch1->id,
            'name' => 'Colombo Bread in Kandy',
            'price' => 110.00,
            'branch_id' => $branch2->id,
        ]);
        $response2->assertRedirect(route('admin.cafeteria.index'));

        $this->assertDatabaseHas('cafeteria_items', [
            'name' => 'Kandy Tea for All',
            'inventory_item_id' => $stockBranch2->id,
            'branch_id' => null,
        ]);

        $this->assertDatabaseHas('cafeteria_items', [
            'name' => 'Colombo Bread in Kandy',
            'inventory_item_id' => $stockBranch1->id,
            'branch_id' => $branch2->id,
        ]);
    }

    public function test_cafeteria_can_create_fresh_stock_and_menu_item_in_one_step(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $branch = \App\Models\Branch::create(['name' => 'Galle Campus', 'code' => 'GAL', 'is_active' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->post(route('admin.cafeteria.items.store'), [
            'stock_source' => 'fresh',
            'name' => 'Fresh Hot Samosa',
            'unit' => 'piece',
            'fresh_stock_quantity' => 60,
            'reorder_level' => 10,
            'price' => 50.00,
            'branch_id' => $branch->id,
            'category' => 'Snacks',
            'description' => 'Crispy potato filling',
        ]);

        $response->assertRedirect(route('admin.cafeteria.index'));
        $response->assertSessionHas('success');

        // Verify fresh stock item was created in inventory
        $stock = InventoryItem::where('name', 'Fresh Hot Samosa')->firstOrFail();
        $this->assertSame(60, $stock->quantity_on_hand);
        $this->assertSame('piece', $stock->unit);
        $this->assertSame($branch->id, $stock->branch_id);

        // Verify cafeteria item was created and linked to this fresh stock
        $menuItem = \App\Models\CafeteriaItem::where('name', 'Fresh Hot Samosa')->firstOrFail();
        $this->assertSame($stock->id, $menuItem->inventory_item_id);
        $this->assertSame('50.00', (string) $menuItem->price);
        $this->assertSame($branch->id, $menuItem->branch_id);

        // Test restocking fresh stock directly from cafeteria
        $restockResponse = $this->actingAs($admin)->post(route('admin.cafeteria.stock.restock', $stock->id), [
            'quantity' => 40,
        ]);
        $restockResponse->assertRedirect(route('admin.cafeteria.index'));
        $this->assertSame(100, $stock->fresh()->quantity_on_hand);

        // Test quick adding standalone stock
        $quickResponse = $this->actingAs($admin)->post(route('admin.cafeteria.stock.store'), [
            'name' => 'Bulk Cooking Oil',
            'unit' => 'liter',
            'initial_quantity' => 25,
            'branch_id' => $branch->id,
        ]);
        $quickResponse->assertRedirect(route('admin.cafeteria.index'));
        $oil = InventoryItem::where('name', 'Bulk Cooking Oil')->firstOrFail();
        $this->assertSame(25, $oil->quantity_on_hand);
    }

    public function test_user_index_search_by_phone_and_name_executes_without_sql_column_error(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'name' => 'System Super Admin',
            'email' => 'admin_search_test@example.com',
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $teacherUser = User::factory()->create([
            'name' => 'Hasan Teacher',
            'email' => 'hasan.teacher@example.com',
            'role' => 'teacher',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '+94771234567',
        ]);

        // Search by partial phone number
        $responsePhone = $this->actingAs($admin)->get(route('admin.users.index', ['search' => '771234']));
        $responsePhone->assertOk();
        $responsePhone->assertSee('Hasan Teacher');

        // Search by partial name matching 'has'
        $responseName = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'has']));
        $responseName->assertOk();
        $responseName->assertSee('Hasan Teacher');
    }

    public function test_student_role_appears_in_users_dropdown_and_filters_correctly(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $studentUser = User::factory()->create([
            'name' => 'Fatima Student',
            'email' => 'fatima.student@example.test',
            'role' => 'student',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $studentUser->assignRole('Student');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk();
        // Verify Student option exists in the Role dropdown
        $response->assertSee('<option value="Student"', false);

        // Verify filtering by role=Student returns the student user
        $filterResponse = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'Student']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Fatima Student');
    }

    public function test_exams_and_results_search_option_works(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class])->run();

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $class = \App\Models\SchoolClass::create(['name' => 'Grade 11', 'section' => 'A']);
        $subject = \App\Models\Subject::create(['name' => 'Advanced Physics', 'code' => 'PHY-301']);
        $term = \App\Models\Term::create([
            'academic_year_id' => \App\Models\AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'])->id,
            'name' => 'Term 1 Test',
            'sequence' => 1,
            'start_date' => '2026-01-10',
            'end_date' => '2026-04-10',
            'is_current' => true,
        ]);

        $exam = \App\Models\Exam::create([
            'name' => 'Quantum Mechanics Midterm',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-03-15',
            'max_marks' => 100,
            'weight' => 30,
            'type' => 'midterm',
            'created_by' => $admin->id,
        ]);

        // Search exam by keyword on admin.exams.index
        $response = $this->actingAs($admin)->get(route('admin.exams.index', ['search' => 'Quantum']));
        $response->assertOk();
        $response->assertSee('Quantum Mechanics Midterm');

        // Search exam by subject code
        $responseCode = $this->actingAs($admin)->get(route('admin.exams.index', ['search' => 'PHY-301']));
        $responseCode->assertOk();
        $responseCode->assertSee('Quantum Mechanics Midterm');

        // Search exam that doesn't match
        $responseNone = $this->actingAs($admin)->get(route('admin.exams.index', ['search' => 'NonExistentExam']));
        $responseNone->assertOk();
        $responseNone->assertDontSee('Quantum Mechanics Midterm');
    }
}
