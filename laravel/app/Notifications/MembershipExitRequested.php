<?php

namespace App\Notifications;

use App\Models\MembershipExitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MembershipExitRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly MembershipExitRequest $exitRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'exit_request_id' => $this->exitRequest->id,
            'organization_id' => $this->exitRequest->organization_id,
            'membership_id' => $this->exitRequest->membership_id,
            'member_name' => $this->exitRequest->membership->user?->name,
            'reason' => $this->exitRequest->reason,
        ];
    }
}
