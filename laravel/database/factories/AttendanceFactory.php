<?php

namespace Database\Factories;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'membership_id' => OrganizationMembership::factory(),
            'status' => AttendanceStatus::Hadir,
            'method' => AttendanceMethod::Manual,
            'checked_in_at' => now(),
        ];
    }
}
