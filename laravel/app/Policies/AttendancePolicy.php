<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

class AttendancePolicy
{
    /**
     * The QR scan-landing page — any member may open it and see why
     * they can/can't check in right now, even outside the Ongoing
     * window (mirrors VotePolicy::view vs respond: the eligibility gate
     * belongs on the action, not on being allowed to see the page).
     */
    public function view(User $user, Event $event): bool
    {
        return $user->roleIn($event->organization) !== null;
    }

    /**
     * Self-check-in — any member, only while the event is actually
     * underway. Not gated on `EventParticipant` — showing up and
     * confirming attendance is independent of having pre-registered
     * interest.
     */
    public function checkIn(User $user, Event $event): bool
    {
        return $user->roleIn($event->organization) !== null && $event->status === EventStatus::Ongoing;
    }

    /**
     * Viewing the attendance list, or the QR code — the chair or this
     * event's own PIC, same boundary as editing the event itself.
     */
    public function manage(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }
}
