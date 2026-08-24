<?php

namespace Database\Factories;

use App\Enums\EventParticipantStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
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
            'status' => EventParticipantStatus::Registered,
        ];
    }
}
