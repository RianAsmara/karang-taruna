<?php

namespace Database\Factories;

use App\Enums\InventoryLoanStatus;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLoan>
 */
class InventoryLoanFactory extends Factory
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
            'inventory_item_id' => InventoryItem::factory(),
            'borrower_membership_id' => OrganizationMembership::factory(),
            'quantity' => 1,
            'status' => InventoryLoanStatus::Borrowed,
            'purpose' => fake()->optional()->sentence(),
            'borrowed_at' => now(),
            'due_date' => now()->addWeek(),
        ];
    }
}
