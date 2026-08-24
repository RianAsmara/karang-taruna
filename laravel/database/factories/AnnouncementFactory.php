<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
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
            'title' => fake()->sentence(6),
            'body' => fake()->paragraph(),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['published_at' => null]);
    }
}
