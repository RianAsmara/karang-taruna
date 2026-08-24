<?php

namespace Database\Factories;

use App\Enums\EventLifecycleStage;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+2 months');

        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->randomElement([
                'Rapat Pemuda Bulanan',
                'Turnamen Voli Antar RT',
                'Bakti Sosial',
                'Malam Tirakatan',
                'Peringatan HUT Kemerdekaan',
            ]),
            'description' => fake()->sentence(12),
            'location' => fake()->streetAddress(),
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+3 hours'),
            'status' => EventStatus::Planned,
            'lifecycle_stage' => EventLifecycleStage::Preparation,
            'created_by' => User::factory(),
        ];
    }
}
