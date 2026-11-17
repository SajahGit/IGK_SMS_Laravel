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
}
