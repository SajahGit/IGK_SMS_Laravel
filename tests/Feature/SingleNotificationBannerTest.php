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
}
