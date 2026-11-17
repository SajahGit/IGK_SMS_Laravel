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
}
