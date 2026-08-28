<?php

namespace App\Actions\Event;

use App\Actions\Member\AwardActivityPointsAction;
use App\Enums\ActivityPointSource;
use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInAttendanceAction
{
    public function __construct(
        private readonly AwardActivityPointsAction $awardActivityPoints,
    ) {}

    public function handle(Event $event, OrganizationMembership $membership, AttendanceMethod $method): Attendance
    {
        return DB::transaction(function () use ($event, $membership, $method) {
            $session = AttendanceSession::firstOrCreate(
                ['event_id' => $event->id],
                ['created_by' => $membership->user_id],
            );

            if ($session->attendances()->where('membership_id', $membership->id)->exists()) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah tercatat hadir di kegiatan ini.',
                ]);
            }

            $attendance = $session->attendances()->create([
                'membership_id' => $membership->id,
                'method' => $method,
                'checked_in_at' => now(),
            ]);

            $this->awardActivityPoints->handle(
                $event->organization,
                $membership,
                ActivityPointSource::EventAttendance,
                $attendance->id,
                $event->title,
            );

            return $attendance;
        });
    }
}
