<?php

namespace App\Actions\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventCommitteeAssigned;
use App\Notifications\EventRescheduled;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class UpdateEventAction
{
    public function __construct(private readonly SyncEventBudgetAction $syncBudget) {}

    /**
     * Update an event, syncing its committee roster and planned budget, and
     * notifying the committee when it's published (Draft -> anything else)
     * or rescheduled (date/location changed on an already-published event).
     * A date in the past can never be "published" as Planned — it goes
     * straight to Completed, mirroring the wizard's own confirmation.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Event $event, User $actor, array $data): Event
    {
        return DB::transaction(function () use ($event, $actor, $data) {
            $previousStatus = $event->status;
            $previousStartAt = $event->start_at;
            $previousLocation = $event->location;

            if (($data['status'] ?? null) === EventStatus::Planned->value
                && Carbon::parse($data['start_at'])->isPast()) {
                $data['status'] = EventStatus::Completed->value;
            }

            $event->update($data);

            if (array_key_exists('committees', $data)) {
                $event->committees()->delete();

                foreach ($data['committees'] ?? [] as $committee) {
                    $event->committees()->create([
                        'membership_id' => $committee['membership_id'],
                        'role_title' => $committee['role_title'] ?? null,
                    ]);
                }
            }

            if (! empty($data['budget_amount'])) {
                $this->syncBudget->handle($event, (int) $data['budget_amount'], $actor);
            }

            $event->refresh();

            $isPublishing = $previousStatus === EventStatus::Draft && $event->status !== EventStatus::Draft;
            $isRescheduled = ! $isPublishing
                && $event->status !== EventStatus::Draft
                && ($previousStartAt->ne($event->start_at) || $previousLocation !== $event->location);

            if ($isPublishing || $isRescheduled) {
                $committeeUsers = $event->committees()->with('membership.user')->get()
                    ->pluck('membership.user')
                    ->filter(fn (User $user) => $user->isNot($actor));

                Notification::send(
                    $committeeUsers,
                    $isPublishing ? new EventCommitteeAssigned($event) : new EventRescheduled($event),
                );
            }

            return $event;
        });
    }
}
