<?php

namespace Database\Factories;

use App\Enums\EventTaskPriority;
use App\Enums\EventTaskStatus;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTask>
 */
class EventTaskFactory extends Factory
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
            'title' => fake()->randomElement([
                'Booking tempat',
                'Pesan konsumsi',
                'Siapkan sound system',
                'Sebar undangan',
                'Buat laporan pertanggungjawaban',
            ]),
            'description' => fake()->optional()->sentence(),
            'status' => EventTaskStatus::Todo,
            'priority' => fake()->randomElement(EventTaskPriority::cases()),
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
            'created_by' => User::factory(),
        ];
    }
}
