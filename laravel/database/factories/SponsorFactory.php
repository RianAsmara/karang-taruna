<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
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
            'name' => fake()->company(),
            'contact_name' => fake()->optional()->name(),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'created_by' => User::factory(),
        ];
    }
}
