<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'qr_token' => (string) Str::ulid(),
            'created_by' => User::factory(),
        ];
    }
}
