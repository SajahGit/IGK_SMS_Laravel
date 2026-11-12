<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('INV-####-??')),
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->randomElement(['Stationery', 'Laboratory', 'Furniture', 'IT Equipment']),
            'unit' => fake()->randomElement(['piece', 'box', 'kit']),
            'quantity_on_hand' => 0,
            'reorder_level' => fake()->numberBetween(0, 20),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
