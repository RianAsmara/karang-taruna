<?php

namespace Database\Factories;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Tenda Pleton', 'Sound System Portable', 'Kursi Lipat', 'Meja Panjang', 'Bola Voli']),
            'category' => fake()->randomElement(InventoryCategory::cases()),
            'quantity' => fake()->numberBetween(1, 20),
            'condition' => InventoryCondition::Baik,
            'location' => fake()->optional()->randomElement(['Gudang RT', 'Sekretariat', 'Rumah Bendahara']),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
