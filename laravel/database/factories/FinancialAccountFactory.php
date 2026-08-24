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
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Kas Pemuda', 'Kas Olahraga', 'Kas Sosial', 'Kas Event']),
        ];
    }
}
