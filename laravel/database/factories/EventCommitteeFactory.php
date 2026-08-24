<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCommittee;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCommittee>
 */
class EventCommitteeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'membership_id' => OrganizationMembership::factory(),
            'role_title' => fake()->randomElement(['Ketua Panitia', 'Sie Konsumsi', 'Sie Acara', 'Sie Dokumentasi', null]),
        ];
    }
}
