<?php

namespace Database\Factories;

use App\Models\FinancialAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
class FinancialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Suffixed with a unique number: the flavor-name pool only has 4
        // entries, and organizations.unique(organization_id, name) means
        // two accounts for the same org can otherwise collide.
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Kas Pemuda', 'Kas Olahraga', 'Kas Sosial', 'Kas Event']).' '.fake()->unique()->numberBetween(1000, 9999),
        ];
    }
}
