<?php

namespace Database\Factories;

use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Models\Organization;
use App\Models\Sponsor;
use App\Models\SponsorContribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsorContribution>
 */
class SponsorContributionFactory extends Factory
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
            'sponsor_id' => Sponsor::factory(),
            'type' => SponsorType::Uang,
            'status' => SponsorContributionStatus::Diajukan,
            'amount' => fake()->numberBetween(100_000, 5_000_000),
            'created_by' => User::factory(),
        ];
    }

    public function inKind(): static
    {
        return $this->state(fn () => [
            'type' => fake()->randomElement([SponsorType::Barang, SponsorType::Jasa]),
            'amount' => null,
            'description' => fake()->sentence(),
        ]);
    }
}
