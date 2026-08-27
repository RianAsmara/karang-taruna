<?php

namespace Database\Factories;

use App\Enums\VoteEligibleScope;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
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
            'question' => 'Apakah kita mengadakan turnamen voli?',
            'anonymous' => false,
            'editable' => true,
            'max_selections' => 1,
            'eligible_scope' => VoteEligibleScope::All,
            'start_at' => now()->subDay(),
            'end_at' => now()->addWeek(),
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'start_at' => now()->subWeeks(2),
            'end_at' => now()->subWeek(),
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(['anonymous' => true]);
    }

    public function notEditable(): static
    {
        return $this->state(['editable' => false]);
    }

    public function pengurusOnly(): static
    {
        return $this->state(['eligible_scope' => VoteEligibleScope::Pengurus]);
    }
}
